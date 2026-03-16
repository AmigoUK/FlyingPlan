import os


class Config:
    SECRET_KEY = os.environ.get("SECRET_KEY", "fp-dev-key-change-in-production")
    SQLALCHEMY_DATABASE_URI = os.environ.get(
        "DATABASE_URL", "sqlite:///flyingplan.db"
    )
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    UPLOAD_FOLDER = os.path.join(
        os.path.abspath(os.path.dirname(__file__)), "instance", "uploads"
    )
    MAX_CONTENT_LENGTH = 32 * 1024 * 1024  # 32 MB
    WTF_CSRF_TIME_LIMIT = 3600
    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = "Lax"
    REMEMBER_COOKIE_DURATION = 14 * 24 * 3600  # 14 days
    REMEMBER_COOKIE_HTTPONLY = True

    # Google OAuth
    GOOGLE_CLIENT_ID = os.environ.get("GOOGLE_CLIENT_ID", "")
    GOOGLE_CLIENT_SECRET = os.environ.get("GOOGLE_CLIENT_SECRET", "")
    GOOGLE_CLIENT_SECRETS_FILE = os.path.join(
        os.path.abspath(os.path.dirname(__file__)), "instance", "client_secret.json"
    )
