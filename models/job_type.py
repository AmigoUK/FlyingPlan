from datetime import datetime, timezone
from extensions import db


class JobType(db.Model):
    __tablename__ = "job_types"

    id = db.Column(db.Integer, primary_key=True)
    value = db.Column(db.String(50), unique=True, nullable=False)
    label = db.Column(db.String(100), nullable=False)
    icon = db.Column(db.String(50), nullable=False, default="bi-briefcase")
    category = db.Column(db.String(30), nullable=False, default="technical")
    is_active = db.Column(db.Boolean, nullable=False, default=True)
    sort_order = db.Column(db.Integer, nullable=False, default=0)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    def __repr__(self):
        return f"<JobType {self.value}>"


DEFAULT_JOB_TYPES = [
    {"value": "aerial_photo",        "label": "Aerial Photography",    "icon": "bi-camera",             "category": "technical"},
    {"value": "inspection",          "label": "Inspection",            "icon": "bi-search",             "category": "technical"},
    {"value": "survey",              "label": "Survey / Mapping",      "icon": "bi-map",                "category": "technical"},
    {"value": "event_celebration",   "label": "Event / Celebration",   "icon": "bi-balloon",            "category": "creative"},
    {"value": "real_estate",         "label": "Real Estate",           "icon": "bi-house",              "category": "creative"},
    {"value": "construction",        "label": "Construction Progress", "icon": "bi-building",           "category": "technical"},
    {"value": "agriculture",         "label": "Agriculture",           "icon": "bi-tree",               "category": "technical"},
    {"value": "emergency_insurance", "label": "Emergency / Insurance", "icon": "bi-shield-exclamation", "category": "technical"},
    {"value": "custom_other",        "label": "Custom / Other",        "icon": "bi-three-dots",         "category": "other"},
]
