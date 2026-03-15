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

    app.register_blueprint(auth_bp)
    app.register_blueprint(public_bp)
    app.register_blueprint(admin_bp, url_prefix="/admin")

    # Create tables and seed admin
    with app.app_context():
        db.create_all()
        _seed_admin()

    return app


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


app = create_app()

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5002, debug=True)
