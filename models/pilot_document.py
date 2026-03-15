from datetime import datetime, timezone
from extensions import db


class PilotDocument(db.Model):
    __tablename__ = "pilot_documents"

    DOC_TYPES = ["certificate", "insurance", "license", "other"]

    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    doc_type = db.Column(db.String(50), default="other")
    label = db.Column(db.String(200), nullable=False)
    original_filename = db.Column(db.String(300), nullable=False)
    stored_filename = db.Column(db.String(300), nullable=False)
    file_size = db.Column(db.Integer)
    mime_type = db.Column(db.String(100))
    expiry_date = db.Column(db.Date)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    user = db.relationship("User", backref=db.backref("documents", cascade="all, delete-orphan", lazy=True))
