from datetime import datetime, timezone
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from extensions import db


class User(UserMixin, db.Model):
    __tablename__ = "users"

    ROLES = ["pilot", "manager", "admin"]
    _ROLE_RANK = {"pilot": 0, "manager": 1, "admin": 2}
    AVAILABILITY_STATUSES = ["available", "on_mission", "unavailable"]

    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    display_name = db.Column(db.String(120), nullable=False)
    password_hash = db.Column(db.String(256), nullable=False)
    is_active_user = db.Column(db.Boolean, default=True)
    role = db.Column(db.String(20), nullable=False, default="admin")
    email = db.Column(db.String(200))
    phone = db.Column(db.String(50))
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    # Pilot profile fields
    flying_id = db.Column(db.String(100))
    operator_id = db.Column(db.String(100))
    flying_id_expiry = db.Column(db.Date)
    operator_id_expiry = db.Column(db.Date)
    insurance_provider = db.Column(db.String(200))
    insurance_policy_no = db.Column(db.String(100))
    insurance_expiry = db.Column(db.Date)
    availability_status = db.Column(db.String(20), default="available")
    pilot_bio = db.Column(db.Text)

    # UK Drone Qualifications
    a2_cofc_expiry = db.Column(db.Date)
    gvc_mr_expiry = db.Column(db.Date)
    gvc_fw_expiry = db.Column(db.Date)

    # Certificate of Competency
    practical_competency_date = db.Column(db.Date)
    mentor_examiner = db.Column(db.String(200))

    # Article 16
    article16_agreed = db.Column(db.Boolean, default=False)
    article16_agreed_date = db.Column(db.Date)

    # Home Address
    address_line1 = db.Column(db.String(200))
    address_line2 = db.Column(db.String(200))
    address_city = db.Column(db.String(100))
    address_county = db.Column(db.String(100))
    address_postcode = db.Column(db.String(20))
    address_country = db.Column(db.String(100), default="United Kingdom")

    @property
    def is_active(self):
        return self.is_active_user

    def has_role_at_least(self, minimum_role):
        return self._ROLE_RANK.get(self.role, 0) >= self._ROLE_RANK.get(minimum_role, 0)

    @property
    def is_pilot(self):
        return self.role == "pilot"

    @property
    def is_manager_or_above(self):
        return self.has_role_at_least("manager")

    def set_password(self, password):
        self.password_hash = generate_password_hash(
            password, method="pbkdf2:sha256"
        )

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    @property
    def flying_id_valid(self):
        from datetime import date
        return bool(self.flying_id and self.flying_id_expiry and self.flying_id_expiry >= date.today())

    @property
    def operator_id_valid(self):
        from datetime import date
        return bool(self.operator_id and self.operator_id_expiry and self.operator_id_expiry >= date.today())
