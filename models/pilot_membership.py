from datetime import datetime, timezone
from extensions import db


class PilotMembership(db.Model):
    __tablename__ = "pilot_memberships"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    org_name = db.Column(db.String(200), nullable=False)
    membership_number = db.Column(db.String(100))
    membership_type = db.Column(db.String(100))
    expiry_date = db.Column(db.Date)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    user = db.relationship("User", backref=db.backref("memberships", cascade="all, delete-orphan", lazy=True))
