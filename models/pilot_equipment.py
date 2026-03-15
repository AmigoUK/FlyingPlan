from datetime import datetime, timezone
from extensions import db


class PilotEquipment(db.Model):
    __tablename__ = "pilot_equipment"

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    drone_model = db.Column(db.String(200), nullable=False)
    serial_number = db.Column(db.String(100))
    registration_id = db.Column(db.String(100))
    notes = db.Column(db.Text)
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    user = db.relationship("User", backref=db.backref("equipment", cascade="all, delete-orphan", lazy=True))
