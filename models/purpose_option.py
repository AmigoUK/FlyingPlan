from datetime import datetime, timezone
from extensions import db


class PurposeOption(db.Model):
    __tablename__ = "purpose_options"

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
        return f"<PurposeOption {self.value}>"


DEFAULT_PURPOSE_OPTIONS = [
    {"value": "marketing",           "label": "Marketing Material",   "icon": "bi-megaphone"},
    {"value": "insurance",           "label": "Insurance Claim",      "icon": "bi-shield-check"},
    {"value": "progress_report",     "label": "Progress Report",      "icon": "bi-graph-up"},
    {"value": "personal",            "label": "Personal Keepsake",    "icon": "bi-heart"},
    {"value": "social_media",        "label": "Social Media",         "icon": "bi-phone"},
    {"value": "real_estate_listing", "label": "Real Estate Listing",  "icon": "bi-house-door"},
    {"value": "legal_evidence",      "label": "Legal / Evidence",     "icon": "bi-file-earmark-text"},
    {"value": "other",               "label": "Other",                "icon": "bi-three-dots"},
]
