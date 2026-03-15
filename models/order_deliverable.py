from datetime import datetime, timezone
from extensions import db


class OrderDeliverable(db.Model):
    __tablename__ = "order_deliverables"

    ALLOWED_EXTENSIONS = {
        "png", "jpg", "jpeg", "gif", "mp4", "mov", "avi",
        "pdf", "zip", "tiff", "tif",
    }

    id = db.Column(db.Integer, primary_key=True)
    order_id = db.Column(db.Integer, db.ForeignKey("orders.id"), nullable=False)
    uploaded_by_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)
    original_filename = db.Column(db.String(300), nullable=False)
    stored_filename = db.Column(db.String(300), nullable=False)
    file_size = db.Column(db.Integer)
    mime_type = db.Column(db.String(100))
    description = db.Column(db.Text)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    uploaded_by = db.relationship("User")
