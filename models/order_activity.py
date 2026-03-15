from datetime import datetime, timezone
from extensions import db


class OrderActivity(db.Model):
    __tablename__ = "order_activities"

    ACTIONS = [
        "created", "assigned", "accepted", "declined",
        "status_changed", "note_added", "deliverable_uploaded",
        "risk_assessment_completed",
    ]

    id = db.Column(db.Integer, primary_key=True)
    order_id = db.Column(db.Integer, db.ForeignKey("orders.id"), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=True)
    action = db.Column(db.String(50), nullable=False)
    old_value = db.Column(db.String(100))
    new_value = db.Column(db.String(100))
    details = db.Column(db.Text)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    user = db.relationship("User")
