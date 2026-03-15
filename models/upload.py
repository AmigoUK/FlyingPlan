from datetime import datetime, timezone
from extensions import db


class Upload(db.Model):
    __tablename__ = "uploads"

    id = db.Column(db.Integer, primary_key=True)
    flight_plan_id = db.Column(
        db.Integer, db.ForeignKey("flight_plans.id", ondelete="CASCADE"), nullable=False
    )
    original_filename = db.Column(db.String(300), nullable=False)
    stored_filename = db.Column(db.String(300), nullable=False)
    file_size = db.Column(db.Integer)
    mime_type = db.Column(db.String(100))
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )
