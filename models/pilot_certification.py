from datetime import datetime, timezone
from extensions import db


class PilotCertification(db.Model):
    __tablename__ = "pilot_certifications"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    cert_name = db.Column(db.String(200), nullable=False)
    issuing_body = db.Column(db.String(200))
    cert_number = db.Column(db.String(100))
    issue_date = db.Column(db.Date)
    expiry_date = db.Column(db.Date)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    user = db.relationship("User", backref=db.backref("certifications", cascade="all, delete-orphan", lazy=True))
