from datetime import datetime, timezone
from extensions import db


class PilotEquipment(db.Model):
    __tablename__ = "pilot_equipment"

    CLASS_MARKS = ['C0', 'C1', 'C2', 'C3', 'C4', 'legacy']
    GREEN_LIGHT_TYPES = ['built_in', 'external', 'none']

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

    # Class mark / type designation
    class_mark = db.Column(db.String(20))  # 'C0','C1','C2','C3','C4','legacy'
    mtom_grams = db.Column(db.Integer)     # Max Take-Off Mass in grams
    has_camera = db.Column(db.Boolean, default=True)

    # Green light (night flying)
    green_light_type = db.Column(db.String(20), default='none')  # 'built_in','external','none'
    green_light_weight_grams = db.Column(db.Integer)  # only if external

    # Operational characteristics
    has_low_speed_mode = db.Column(db.Boolean, default=False)  # <=3 m/s built-in, C2 only
    remote_id_capable = db.Column(db.Boolean, default=False)
    max_speed_ms = db.Column(db.Float)       # for SORA iGRC (max commanded airspeed)
    max_dimension_m = db.Column(db.Float)    # for SORA iGRC (largest dimension)

    user = db.relationship("User", backref=db.backref("equipment", cascade="all, delete-orphan", lazy=True))

    @property
    def effective_mtom_grams(self):
        """MTOM including external green light weight."""
        base = self.mtom_grams or 0
        if self.green_light_type == 'external':
            base += (self.green_light_weight_grams or 0)
        return base

    @property
    def class_mark_display(self):
        """Human-readable class mark."""
        if not self.class_mark:
            return 'Not set'
        return self.class_mark

    @property
    def mtom_display(self):
        """Human-readable MTOM."""
        if not self.mtom_grams:
            return 'Not set'
        if self.mtom_grams >= 1000:
            return f"{self.mtom_grams / 1000:.1f} kg"
        return f"{self.mtom_grams} g"
