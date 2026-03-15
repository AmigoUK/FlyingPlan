from datetime import datetime, timezone
from extensions import db


class HeardAboutOption(db.Model):
    __tablename__ = "heard_about_options"

    id = db.Column(db.Integer, primary_key=True)
    value = db.Column(db.String(50), unique=True, nullable=False)
    label = db.Column(db.String(100), nullable=False)
    icon = db.Column(db.String(50), nullable=False, default="bi-question-circle")
    is_active = db.Column(db.Boolean, nullable=False, default=True)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    def __repr__(self):
        return f"<HeardAboutOption {self.value}>"


DEFAULT_HEARD_ABOUT_OPTIONS = [
    {"value": "google",       "label": "Google Search", "icon": "bi-google"},
    {"value": "social_media", "label": "Social Media",  "icon": "bi-phone"},
    {"value": "referral",     "label": "Referral",      "icon": "bi-people"},
    {"value": "website",      "label": "Website",       "icon": "bi-globe"},
    {"value": "other",        "label": "Other",         "icon": "bi-three-dots"},
]
