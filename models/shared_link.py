from datetime import datetime, timezone, timedelta
from extensions import db
import secrets


class SharedLink(db.Model):
    __tablename__ = "shared_links"

    id = db.Column(db.Integer, primary_key=True)
    flight_plan_id = db.Column(
        db.Integer, db.ForeignKey("flight_plans.id", ondelete="CASCADE"), nullable=False
    )
    token = db.Column(db.String(64), unique=True, nullable=False, index=True)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )
    expires_at = db.Column(db.DateTime, nullable=True)
    is_active = db.Column(db.Boolean, default=True, nullable=False)

    flight_plan = db.relationship("FlightPlan", backref="shared_links")

    def generate_token(self):
        self.token = secrets.token_urlsafe(32)

    @property
    def is_expired(self):
        if not self.expires_at:
            return False
        exp = self.expires_at
        if not exp.tzinfo:
            exp = exp.replace(tzinfo=timezone.utc)
        return datetime.now(timezone.utc) > exp

    @property
    def is_valid(self):
        return self.is_active and not self.is_expired
