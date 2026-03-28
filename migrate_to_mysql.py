"""Migrate FlyingPlan data from SQLite to MySQL/MariaDB.

Usage:
    cd /var/www/html/FlyingPlan
    python migrate_to_mysql.py

Reads from the SQLite database at instance/flyingplan.db and writes to the
MySQL database specified by MYSQL_URL (env var) or the default connection string.
"""

import json
import os
import sqlite3
import sys
from datetime import datetime, date

from sqlalchemy import create_engine, text, MetaData

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
SQLITE_PATH = os.path.join(
    os.path.dirname(os.path.abspath(__file__)), "instance", "flyingplan.db"
)
MYSQL_URL = os.environ.get(
    "MYSQL_URL",
    "mysql+pymysql://flyingplan:FlyingPlan2026!@localhost/flyingplan?charset=utf8mb4",
)

# FK-safe insertion order (parent tables first)
TABLE_ORDER = [
    # Tier 0: no FK dependencies
    "app_settings",
    "job_types",
    "purpose_options",
    "heard_about_options",
    # Tier 1: no FK dependencies
    "users",
    # Tier 2: depends on users
    "flight_plans",
    "pilot_equipment",
    "pilot_certifications",
    "pilot_documents",
    "pilot_memberships",
    # Tier 3: depends on flight_plans
    "waypoints",
    "pois",
    "uploads",
    "shared_links",
    # Tier 4: depends on flight_plans + users + pilot_equipment
    "orders",
    # Tier 5: depends on orders + users
    "risk_assessments",
    "order_activities",
    "order_deliverables",
]

# Columns that store boolean values (SQLite 0/1 -> Python bool)
BOOLEAN_COLUMNS = {
    "app_settings": [
        "show_heard_about", "show_customer_type_toggle", "show_purpose_fields",
        "show_output_format", "guide_mode", "dark_mode",
    ],
    "users": ["is_active_user", "article16_agreed"],
    "flight_plans": ["consent_given"],
    "orders": ["risk_assessment_completed"],
    "risk_assessments": [
        "site_ground_hazards", "site_obstacles_mapped", "site_50m_separation",
        "site_150m_residential", "airspace_frz_checked", "airspace_restricted_checked",
        "airspace_notams_reviewed", "airspace_max_altitude_confirmed",
        "weather_acceptable", "equip_condition_ok", "equip_battery_adequate",
        "equip_propellers_ok", "equip_gps_lock", "equip_remote_ok",
        "equip_remote_id_active", "imsafe_illness", "imsafe_medication",
        "imsafe_stress", "imsafe_alcohol", "imsafe_fatigue", "imsafe_eating",
        "perms_flyer_id_valid", "perms_operator_id_displayed", "perms_insurance_valid",
        "perms_authorizations_checked", "emergency_landing_site",
        "emergency_contacts_confirmed", "emergency_contingency_plan",
        "pilot_declaration", "night_green_light_fitted", "night_green_light_on",
        "night_vlos_maintainable", "night_orientation_visible",
        "a2_distance_confirmed", "a2_low_speed_active", "a2_segregation_assessed",
        "a3_150m_from_areas", "a3_50m_from_people", "a3_50m_from_buildings",
        "specific_ops_manual_reviewed", "specific_insurance_confirmed",
        "specific_oa_valid",
    ],
    "pilot_equipment": [
        "is_active", "has_camera", "has_low_speed_mode", "remote_id_capable",
    ],
    "shared_links": ["is_active"],
    "job_types": ["is_active"],
    "purpose_options": ["is_active"],
    "heard_about_options": ["is_active"],
}

# Columns that store datetime values (SQLite TEXT -> Python datetime)
DATETIME_COLUMNS = {
    "users": ["created_at"],
    "flight_plans": ["created_at", "updated_at"],
    "orders": [
        "assigned_at", "accepted_at", "started_at", "completed_at",
        "delivered_at", "closed_at", "created_at", "updated_at",
        "category_determined_at",
    ],
    "risk_assessments": ["created_at"],
    "pilot_equipment": ["created_at"],
    "pilot_certifications": ["created_at"],
    "pilot_documents": ["created_at"],
    "pilot_memberships": ["created_at"],
    "uploads": ["created_at"],
    "shared_links": ["created_at", "expires_at"],
    "order_activities": ["created_at"],
    "order_deliverables": ["created_at"],
    "job_types": ["created_at"],
    "purpose_options": ["created_at"],
    "heard_about_options": ["created_at"],
}

# Columns that store date values (SQLite TEXT -> Python date)
DATE_COLUMNS = {
    "users": [
        "flying_id_expiry", "operator_id_expiry", "insurance_expiry",
        "a2_cofc_expiry", "gvc_mr_expiry", "gvc_fw_expiry",
        "practical_competency_date", "article16_agreed_date", "oa_expiry",
    ],
    "orders": ["scheduled_date"],
    "pilot_certifications": ["issue_date", "expiry_date"],
    "pilot_documents": ["expiry_date"],
    "pilot_memberships": ["expiry_date"],
}

# Columns that store JSON as TEXT (validate during migration)
JSON_COLUMNS = {
    "flight_plans": ["area_polygon", "shot_types"],
    "orders": ["category_blockers"],
}


# ---------------------------------------------------------------------------
# Type conversion helpers
# ---------------------------------------------------------------------------
def parse_datetime(value):
    """Parse a SQLite datetime text string into a Python datetime."""
    if value is None:
        return None
    if isinstance(value, datetime):
        return value
    for fmt in (
        "%Y-%m-%d %H:%M:%S.%f",
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%dT%H:%M:%S.%f",
        "%Y-%m-%dT%H:%M:%S",
    ):
        try:
            return datetime.strptime(value, fmt)
        except ValueError:
            continue
    print(f"  WARNING: Cannot parse datetime: {value!r}")
    return None


def parse_date(value):
    """Parse a SQLite date text string into a Python date."""
    if value is None:
        return None
    if isinstance(value, date):
        return value
    try:
        return datetime.strptime(value, "%Y-%m-%d").date()
    except ValueError:
        print(f"  WARNING: Cannot parse date: {value!r}")
        return None


def convert_bool(value, nullable=False):
    """Convert SQLite 0/1/None to Python bool."""
    if value is None:
        return None if nullable else False
    return bool(value)


def validate_json(value, table, col, row_id):
    """Validate that a TEXT field contains valid JSON."""
    if value is None or value == "":
        return value
    try:
        json.loads(value)
        return value
    except json.JSONDecodeError:
        print(f"  WARNING: Invalid JSON in {table}.{col} id={row_id}: {value[:80]!r}")
        return None


# ---------------------------------------------------------------------------
# Core migration logic
# ---------------------------------------------------------------------------
def transform_row(table, columns, row_dict):
    """Apply type conversions to a single row."""
    result = dict(row_dict)

    # Boolean conversions
    for col in BOOLEAN_COLUMNS.get(table, []):
        if col in result:
            result[col] = convert_bool(result[col])

    # DateTime conversions
    for col in DATETIME_COLUMNS.get(table, []):
        if col in result:
            result[col] = parse_datetime(result[col])

    # Date conversions
    for col in DATE_COLUMNS.get(table, []):
        if col in result:
            result[col] = parse_date(result[col])

    # JSON validation
    for col in JSON_COLUMNS.get(table, []):
        if col in result:
            result[col] = validate_json(result[col], table, col, result.get("id"))

    return result


def migrate_table(src_conn, mysql_engine, table):
    """Migrate all rows from one SQLite table to MySQL."""
    cursor = src_conn.execute(f'SELECT * FROM "{table}"')
    columns = [desc[0] for desc in cursor.description]
    rows = cursor.fetchall()

    if not rows:
        print(f"  {table}: 0 rows (empty)")
        return 0

    # Build INSERT statement with backtick-quoted column names (for reserved words like `index`)
    col_list = ", ".join(f"`{c}`" for c in columns)
    param_list = ", ".join(f":{c}" for c in columns)
    insert_sql = text(f"INSERT INTO `{table}` ({col_list}) VALUES ({param_list})")

    count = 0
    with mysql_engine.begin() as conn:
        for row in rows:
            row_dict = dict(zip(columns, row))
            transformed = transform_row(table, columns, row_dict)
            conn.execute(insert_sql, transformed)
            count += 1

    print(f"  {table}: {count} rows migrated")
    return count


def reset_auto_increment(mysql_engine, table):
    """Reset AUTO_INCREMENT to max(id)+1 for a table."""
    with mysql_engine.begin() as conn:
        result = conn.execute(text(f"SELECT MAX(id) FROM `{table}`"))
        max_id = result.scalar() or 0
        if max_id > 0:
            conn.execute(text(f"ALTER TABLE `{table}` AUTO_INCREMENT = {max_id + 1}"))


# ---------------------------------------------------------------------------
# Schema creation using SQLAlchemy models
# ---------------------------------------------------------------------------
def create_mysql_schema(mysql_url):
    """Create all tables in MySQL using the Flask-SQLAlchemy models."""
    # We need Flask app context for db.create_all()
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
    os.environ["DATABASE_URL"] = mysql_url

    from app import create_app
    app = create_app()

    with app.app_context():
        from extensions import db
        db.create_all()
        print("  MySQL schema created from SQLAlchemy models")

    return app


# ---------------------------------------------------------------------------
# Verification
# ---------------------------------------------------------------------------
def verify_migration(src_conn, mysql_engine):
    """Verify data integrity after migration."""
    print("\n--- VERIFICATION ---")
    all_ok = True

    # 1. Row count comparison
    print("\n1. Row counts:")
    for table in TABLE_ORDER:
        src_count = src_conn.execute(f'SELECT COUNT(*) FROM "{table}"').fetchone()[0]
        with mysql_engine.connect() as conn:
            mysql_count = conn.execute(text(f"SELECT COUNT(*) FROM `{table}`")).scalar()
        status = "OK" if src_count == mysql_count else "MISMATCH"
        if status == "MISMATCH":
            all_ok = False
        print(f"  {table}: SQLite={src_count} MySQL={mysql_count} [{status}]")

    # 2. FK integrity check
    print("\n2. Foreign key integrity:")
    fk_checks = [
        ("waypoints", "flight_plan_id", "flight_plans", "id"),
        ("pois", "flight_plan_id", "flight_plans", "id"),
        ("uploads", "flight_plan_id", "flight_plans", "id"),
        ("shared_links", "flight_plan_id", "flight_plans", "id"),
        ("orders", "flight_plan_id", "flight_plans", "id"),
        ("orders", "pilot_id", "users", "id"),
        ("orders", "equipment_id", "pilot_equipment", "id"),
        ("order_activities", "order_id", "orders", "id"),
        ("order_deliverables", "order_id", "orders", "id"),
        ("risk_assessments", "order_id", "orders", "id"),
        ("risk_assessments", "pilot_id", "users", "id"),
        ("pilot_equipment", "user_id", "users", "id"),
        ("pilot_certifications", "user_id", "users", "id"),
        ("pilot_documents", "user_id", "users", "id"),
        ("pilot_memberships", "user_id", "users", "id"),
    ]
    with mysql_engine.connect() as conn:
        for child, fk_col, parent, pk_col in fk_checks:
            result = conn.execute(text(f"""
                SELECT COUNT(*) FROM `{child}` c
                LEFT JOIN `{parent}` p ON c.`{fk_col}` = p.`{pk_col}`
                WHERE c.`{fk_col}` IS NOT NULL AND p.`{pk_col}` IS NULL
            """))
            orphans = result.scalar()
            status = "OK" if orphans == 0 else f"FAIL ({orphans} orphans)"
            if orphans > 0:
                all_ok = False
            print(f"  {child}.{fk_col} -> {parent}.{pk_col}: {status}")

    # 3. Password hash test
    print("\n3. Password hash test:")
    try:
        from werkzeug.security import check_password_hash
        with mysql_engine.connect() as conn:
            result = conn.execute(
                text("SELECT password_hash FROM `users` WHERE username = 'admin'")
            )
            admin_hash = result.scalar()
            if admin_hash and check_password_hash(admin_hash, "admin123"):
                print("  admin password hash: OK")
            else:
                print("  admin password hash: FAIL (hash doesn't match 'admin123')")
                all_ok = False
    except Exception as e:
        print(f"  Password test error: {e}")
        all_ok = False

    # 4. JSON field validation
    print("\n4. JSON field validation:")
    with mysql_engine.connect() as conn:
        for table, cols in JSON_COLUMNS.items():
            for col in cols:
                result = conn.execute(
                    text(f"SELECT id, `{col}` FROM `{table}` WHERE `{col}` IS NOT NULL AND `{col}` != ''")
                )
                bad = 0
                for row in result:
                    try:
                        json.loads(row[1])
                    except (json.JSONDecodeError, TypeError):
                        bad += 1
                status = "OK" if bad == 0 else f"FAIL ({bad} invalid)"
                if bad > 0:
                    all_ok = False
                print(f"  {table}.{col}: {status}")

    # 5. Boolean spot check
    print("\n5. Boolean spot check (app_settings):")
    with mysql_engine.connect() as conn:
        result = conn.execute(
            text("SELECT show_heard_about, guide_mode, dark_mode FROM `app_settings` LIMIT 1")
        )
        row = result.fetchone()
        if row:
            valid = all(v in (0, 1, True, False) for v in row)
            print(f"  Values: {tuple(row)} -> {'OK' if valid else 'FAIL'}")
            if not valid:
                all_ok = False
        else:
            print("  No app_settings row found")

    print(f"\n{'='*50}")
    print(f"MIGRATION {'PASSED' if all_ok else 'FAILED'}")
    print(f"{'='*50}")
    return all_ok


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
def main():
    print("FlyingPlan SQLite -> MySQL Migration")
    print("=" * 50)

    # Check SQLite source exists
    if not os.path.exists(SQLITE_PATH):
        print(f"ERROR: SQLite database not found at {SQLITE_PATH}")
        sys.exit(1)

    print(f"\nSource: {SQLITE_PATH}")
    print(f"Target: {MYSQL_URL.split('@')[1] if '@' in MYSQL_URL else MYSQL_URL}")

    # Connect to SQLite source
    src_conn = sqlite3.connect(SQLITE_PATH)
    src_conn.row_factory = None  # raw tuples

    # Create MySQL schema via Flask app
    print("\n--- CREATING SCHEMA ---")
    app = create_mysql_schema(MYSQL_URL)
    mysql_engine = create_engine(MYSQL_URL)

    # Disable FK checks and truncate seeded data
    print("\n--- MIGRATING DATA ---")
    with mysql_engine.begin() as conn:
        conn.execute(text("SET FOREIGN_KEY_CHECKS=0"))
        for table in TABLE_ORDER:
            conn.execute(text(f"TRUNCATE TABLE `{table}`"))
        print("  Truncated all tables (removing seeded defaults)")

    # Migrate each table in FK-safe order
    total_rows = 0
    for table in TABLE_ORDER:
        total_rows += migrate_table(src_conn, mysql_engine, table)

    # Re-enable FK checks
    with mysql_engine.begin() as conn:
        conn.execute(text("SET FOREIGN_KEY_CHECKS=1"))

    # Reset AUTO_INCREMENT counters
    print("\n--- RESETTING AUTO_INCREMENT ---")
    for table in TABLE_ORDER:
        reset_auto_increment(mysql_engine, table)
    print(f"  Done for all {len(TABLE_ORDER)} tables")

    print(f"\nTotal rows migrated: {total_rows}")

    # Verify
    verify_migration(src_conn, mysql_engine)

    src_conn.close()
    mysql_engine.dispose()


if __name__ == "__main__":
    main()
