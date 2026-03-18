import os
from flask import Flask
from config import Config
from extensions import db, login_manager, csrf


def create_app(config_class=None):
    app = Flask(__name__)
    app.config.from_object(config_class or Config)

    # Ensure upload folder exists
    os.makedirs(app.config["UPLOAD_FOLDER"], exist_ok=True)

    # Init extensions
    db.init_app(app)
    login_manager.init_app(app)
    csrf.init_app(app)
    login_manager.login_view = "auth.login"
    login_manager.login_message_category = "warning"

    # User loader
    from models.user import User

    @login_manager.user_loader
    def load_user(user_id):
        return db.session.get(User, int(user_id))

    # Jinja filters
    from datetime import datetime, timezone

    @app.template_filter("relative_date")
    def relative_date_filter(value):
        if not value:
            return ""
        now = datetime.now(timezone.utc)
        if not value.tzinfo:
            value = value.replace(tzinfo=timezone.utc)
        delta = (now.date() - value.date()).days
        if delta == 0:
            return "Today"
        elif delta == 1:
            return "Yesterday"
        elif delta == -1:
            return "Tomorrow"
        elif delta > 0:
            return f"{delta} days ago"
        else:
            return f"In {-delta} days"

    # Register blueprints
    from blueprints.auth import auth_bp
    from blueprints.public import public_bp
    from blueprints.admin import admin_bp
    from blueprints.settings import settings_bp
    from blueprints.pilots import pilots_bp
    from blueprints.orders import orders_bp
    from blueprints.pilot import pilot_bp
    from blueprints.shared import shared_bp
    from blueprints.help import help_bp

    app.register_blueprint(auth_bp)
    app.register_blueprint(public_bp)
    app.register_blueprint(admin_bp, url_prefix="/admin")
    app.register_blueprint(settings_bp, url_prefix="/admin/settings")
    app.register_blueprint(pilots_bp, url_prefix="/admin/pilots")
    app.register_blueprint(orders_bp, url_prefix="/admin/orders")
    app.register_blueprint(pilot_bp, url_prefix="/pilot")
    app.register_blueprint(shared_bp, url_prefix="/shared")
    app.register_blueprint(help_bp, url_prefix="/help")

    # Context processor — inject settings and lookup data into all templates
    from models.app_settings import AppSettings
    from models.job_type import JobType
    from models.purpose_option import PurposeOption
    from models.heard_about_option import HeardAboutOption

    @app.context_processor
    def inject_globals():
        try:
            settings = AppSettings.get()
            active_job_types = JobType.query.filter_by(is_active=True).order_by(
                JobType.sort_order, JobType.id
            ).all()
            active_purposes = PurposeOption.query.filter_by(is_active=True).order_by(
                PurposeOption.sort_order, PurposeOption.id
            ).all()
            active_heard_about = HeardAboutOption.query.filter_by(is_active=True).order_by(
                HeardAboutOption.sort_order, HeardAboutOption.id
            ).all()
        except Exception:
            # Tables may not exist yet during initial create_all
            return {}
        return {
            "app_settings": settings,
            "active_job_types": active_job_types,
            "active_purposes": active_purposes,
            "active_heard_about": active_heard_about,
        }

    # Ensure all models are imported for create_all
    import models.shared_link  # noqa: F401

    # Create tables and seed data
    with app.app_context():
        db.create_all()
        _run_migrations()
        _seed_admin()
        _seed_pilot()
        _seed_lookup_tables()

    return app


def _run_migrations():
    """Add new columns to tables if missing (CRM ALTER TABLE pattern)."""
    from sqlalchemy import inspect, text
    inspector = inspect(db.engine)

    # Flight plans table
    existing = {col["name"] for col in inspector.get_columns("flight_plans")}

    new_columns = [
        ("customer_type", "VARCHAR(10) DEFAULT 'private'"),
        ("business_abn", "VARCHAR(50)"),
        ("billing_contact", "VARCHAR(200)"),
        ("billing_email", "VARCHAR(200)"),
        ("purchase_order", "VARCHAR(100)"),
        ("footage_purpose", "VARCHAR(50)"),
        ("footage_purpose_other", "VARCHAR(300)"),
        ("output_format", "VARCHAR(30)"),
        ("video_duration", "VARCHAR(100)"),
        ("shot_types", "TEXT"),
        ("delivery_timeline", "VARCHAR(50)"),
        ("drone_model", "VARCHAR(50) DEFAULT 'mini_4_pro'"),
    ]

    for col_name, col_type in new_columns:
        if col_name not in existing:
            db.session.execute(
                text(f"ALTER TABLE flight_plans ADD COLUMN {col_name} {col_type}")
            )

    # Users table
    user_cols = {col["name"] for col in inspector.get_columns("users")}
    user_new_columns = [
        ("role", "VARCHAR(20) NOT NULL DEFAULT 'admin'"),
        ("email", "VARCHAR(200)"),
        ("phone", "VARCHAR(50)"),
        ("flying_id", "VARCHAR(100)"),
        ("operator_id", "VARCHAR(100)"),
        ("insurance_provider", "VARCHAR(200)"),
        ("insurance_policy_no", "VARCHAR(100)"),
        ("insurance_expiry", "DATE"),
        ("availability_status", "VARCHAR(20) DEFAULT 'available'"),
        ("pilot_bio", "TEXT"),
        ("flying_id_expiry", "DATE"),
        ("operator_id_expiry", "DATE"),
        ("a2_cofc_expiry", "DATE"),
        ("gvc_mr_expiry", "DATE"),
        ("gvc_fw_expiry", "DATE"),
        ("practical_competency_date", "DATE"),
        ("mentor_examiner", "VARCHAR(200)"),
        ("article16_agreed", "BOOLEAN DEFAULT 0"),
        ("article16_agreed_date", "DATE"),
        ("address_line1", "VARCHAR(200)"),
        ("address_line2", "VARCHAR(200)"),
        ("address_city", "VARCHAR(100)"),
        ("address_county", "VARCHAR(100)"),
        ("address_postcode", "VARCHAR(20)"),
        ("address_country", "VARCHAR(100) DEFAULT 'United Kingdom'"),
    ]

    for col_name, col_type in user_new_columns:
        if col_name not in user_cols:
            db.session.execute(
                text(f"ALTER TABLE users ADD COLUMN {col_name} {col_type}")
            )

    # Orders table
    if inspector.has_table("orders"):
        order_cols = {col["name"] for col in inspector.get_columns("orders")}
        order_new_columns = [
            ("risk_assessment_completed", "BOOLEAN NOT NULL DEFAULT 0"),
            ("equipment_id", "INTEGER REFERENCES pilot_equipment(id)"),
            ("time_of_day", "VARCHAR(20)"),
            ("proximity_to_people", "VARCHAR(30)"),
            ("environment_type", "VARCHAR(30)"),
            ("proximity_to_buildings", "VARCHAR(20)"),
            ("airspace_type", "VARCHAR(20)"),
            ("vlos_type", "VARCHAR(20)"),
            ("speed_mode", "VARCHAR(20)"),
            ("operational_category", "VARCHAR(30)"),
            ("category_determined_at", "DATETIME"),
            ("category_blockers", "TEXT"),
        ]
        for col_name, col_type in order_new_columns:
            if col_name not in order_cols:
                db.session.execute(
                    text(f"ALTER TABLE orders ADD COLUMN {col_name} {col_type}")
                )

    # Pilot equipment table
    if inspector.has_table("pilot_equipment"):
        equip_cols = {col["name"] for col in inspector.get_columns("pilot_equipment")}
        equip_new_columns = [
            ("class_mark", "VARCHAR(20)"),
            ("mtom_grams", "INTEGER"),
            ("has_camera", "BOOLEAN DEFAULT 1"),
            ("green_light_type", "VARCHAR(20) DEFAULT 'none'"),
            ("green_light_weight_grams", "INTEGER"),
            ("has_low_speed_mode", "BOOLEAN DEFAULT 0"),
            ("remote_id_capable", "BOOLEAN DEFAULT 0"),
            ("max_speed_ms", "FLOAT"),
            ("max_dimension_m", "FLOAT"),
        ]
        for col_name, col_type in equip_new_columns:
            if col_name not in equip_cols:
                db.session.execute(
                    text(f"ALTER TABLE pilot_equipment ADD COLUMN {col_name} {col_type}")
                )

    # Risk assessments table
    if inspector.has_table("risk_assessments"):
        ra_cols = {col["name"] for col in inspector.get_columns("risk_assessments")}
        ra_new_columns = [
            ("operational_category", "VARCHAR(30)"),
            ("category_version", "INTEGER DEFAULT 2"),
            ("night_green_light_fitted", "BOOLEAN DEFAULT 0"),
            ("night_green_light_on", "BOOLEAN DEFAULT 0"),
            ("night_vlos_maintainable", "BOOLEAN DEFAULT 0"),
            ("night_orientation_visible", "BOOLEAN DEFAULT 0"),
            ("a2_distance_confirmed", "BOOLEAN DEFAULT 0"),
            ("a2_low_speed_active", "BOOLEAN DEFAULT 0"),
            ("a2_segregation_assessed", "BOOLEAN DEFAULT 0"),
            ("a3_150m_from_areas", "BOOLEAN DEFAULT 0"),
            ("a3_50m_from_people", "BOOLEAN DEFAULT 0"),
            ("a3_50m_from_buildings", "BOOLEAN DEFAULT 0"),
            ("specific_ops_manual_reviewed", "BOOLEAN DEFAULT 0"),
            ("specific_insurance_confirmed", "BOOLEAN DEFAULT 0"),
            ("specific_oa_valid", "BOOLEAN DEFAULT 0"),
        ]
        for col_name, col_type in ra_new_columns:
            if col_name not in ra_cols:
                db.session.execute(
                    text(f"ALTER TABLE risk_assessments ADD COLUMN {col_name} {col_type}")
                )
        # Set category_version = 1 for existing rows (legacy assessments)
        if "category_version" not in ra_cols:
            db.session.execute(
                text("UPDATE risk_assessments SET category_version = 1 WHERE category_version IS NULL OR category_version = 2")
            )

    # App settings table
    if inspector.has_table("app_settings"):
        settings_cols = {col["name"] for col in inspector.get_columns("app_settings")}
        if "dark_mode" not in settings_cols:
            db.session.execute(text("ALTER TABLE app_settings ADD COLUMN dark_mode BOOLEAN NOT NULL DEFAULT 0"))

    # Users table — new cert fields
    user_cert_columns = [
        ("a2_cofc_number", "VARCHAR(100)"),
        ("gvc_level", "VARCHAR(20)"),
        ("gvc_cert_number", "VARCHAR(100)"),
        ("oa_type", "VARCHAR(30)"),
        ("oa_reference", "VARCHAR(100)"),
        ("oa_expiry", "DATE"),
    ]
    for col_name, col_type in user_cert_columns:
        if col_name not in user_cols:
            db.session.execute(
                text(f"ALTER TABLE users ADD COLUMN {col_name} {col_type}")
            )

    db.session.commit()


def _seed_admin():
    from models.user import User

    if User.query.first() is None:
        admin = User(
            username="admin",
            display_name="Admin",
        )
        admin.set_password("admin123")
        db.session.add(admin)
        db.session.commit()


def _seed_pilot():
    from models.user import User

    # Only create default pilot if no pilots exist at all
    if not User.query.filter_by(role="pilot").first():
        pilot = User(
            username="pilot1",
            display_name="Demo Pilot",
            role="pilot",
            flying_id="CAA-12345",
            operator_id="REOC-6789",
        )
        pilot.set_password("pilot123")
        db.session.add(pilot)
        db.session.commit()


def _seed_lookup_tables():
    from models.job_type import JobType, DEFAULT_JOB_TYPES
    from models.purpose_option import PurposeOption, DEFAULT_PURPOSE_OPTIONS
    from models.heard_about_option import HeardAboutOption, DEFAULT_HEARD_ABOUT_OPTIONS
    from models.app_settings import AppSettings

    # Ensure singleton settings exist
    AppSettings.get()

    # Seed job types if table is empty
    if JobType.query.first() is None:
        for i, jt in enumerate(DEFAULT_JOB_TYPES):
            db.session.add(JobType(sort_order=i, **jt))
        db.session.commit()

    # Seed purpose options if table is empty
    if PurposeOption.query.first() is None:
        for i, po in enumerate(DEFAULT_PURPOSE_OPTIONS):
            db.session.add(PurposeOption(sort_order=i, **po))
        db.session.commit()

    # Seed heard-about options if table is empty
    if HeardAboutOption.query.first() is None:
        for i, ha in enumerate(DEFAULT_HEARD_ABOUT_OPTIONS):
            db.session.add(HeardAboutOption(sort_order=i, **ha))
        db.session.commit()


app = create_app()


@app.cli.command("seed-demo")
def seed_demo_command():
    """Wipe DB and load comprehensive demo data."""
    from seed_demo import seed_demo_data
    seed_demo_data()
    print("Demo data seeded successfully.")


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5002, debug=True,
            ssl_context=('certs/cert.pem', 'certs/key.pem'))
