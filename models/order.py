from datetime import datetime, timezone
from extensions import db


class Order(db.Model):
    __tablename__ = "orders"

    STATUSES = [
        "pending_assignment", "assigned", "accepted", "in_progress",
        "flight_complete", "delivered", "closed", "declined",
    ]

    id = db.Column(db.Integer, primary_key=True)
    flight_plan_id = db.Column(
        db.Integer, db.ForeignKey("flight_plans.id"), unique=True, nullable=False
    )
    pilot_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=True)
    assigned_by_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=True)
    status = db.Column(db.String(30), default="pending_assignment", nullable=False)

    scheduled_date = db.Column(db.Date)
    scheduled_time = db.Column(db.String(50))
    assignment_notes = db.Column(db.Text)
    pilot_notes = db.Column(db.Text)
    completion_notes = db.Column(db.Text)
    decline_reason = db.Column(db.Text)

    assigned_at = db.Column(db.DateTime)
    accepted_at = db.Column(db.DateTime)
    started_at = db.Column(db.DateTime)
    completed_at = db.Column(db.DateTime)
    delivered_at = db.Column(db.DateTime)
    closed_at = db.Column(db.DateTime)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )
    updated_at = db.Column(
        db.DateTime,
        default=lambda: datetime.now(timezone.utc),
        onupdate=lambda: datetime.now(timezone.utc),
    )

    flight_plan = db.relationship(
        "FlightPlan", backref=db.backref("order", uselist=False)
    )
    pilot = db.relationship(
        "User", foreign_keys=[pilot_id],
        backref=db.backref("pilot_orders", lazy=True),
    )
    assigned_by = db.relationship(
        "User", foreign_keys=[assigned_by_id],
    )
    activity_log = db.relationship(
        "OrderActivity", backref="order",
        cascade="all, delete-orphan", lazy=True,
        order_by="OrderActivity.created_at.desc()",
    )
    deliverables = db.relationship(
        "OrderDeliverable", backref="order",
        cascade="all, delete-orphan", lazy=True,
    )
