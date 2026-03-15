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

    app.register_blueprint(auth_bp)
    app.register_blueprint(public_bp)
    app.register_blueprint(admin_bp, url_prefix="/admin")
    app.register_blueprint(settings_bp, url_prefix="/admin/settings")
    app.register_blueprint(pilots_bp, url_prefix="/admin/pilots")
    app.register_blueprint(orders_bp, url_prefix="/admin/orders")
    app.register_blueprint(pilot_bp, url_prefix="/pilot")

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
    ]

    for col_name, col_type in user_new_columns:
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

    if not User.query.filter_by(username="pilot1").first():
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

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5002, debug=True)
