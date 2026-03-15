from datetime import datetime, timezone
from extensions import db


class RiskAssessment(db.Model):
    __tablename__ = "risk_assessments"

    RISK_LEVELS = ["low", "medium", "high"]
    DECISIONS = ["proceed", "proceed_with_mitigations", "abort"]

    id = db.Column(db.Integer, primary_key=True)
    order_id = db.Column(
        db.Integer, db.ForeignKey("orders.id"), unique=True, nullable=False
    )
    pilot_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=False)

    # ── 1. Site Assessment (4 checks) ──────────────────────────────
    site_ground_hazards = db.Column(db.Boolean, default=False, nullable=False)
    site_obstacles_mapped = db.Column(db.Boolean, default=False, nullable=False)
    site_50m_separation = db.Column(db.Boolean, default=False, nullable=False)
    site_150m_residential = db.Column(db.Boolean, default=False, nullable=False)

    # ── 2. Airspace Check (4 checks + altitude) ───────────────────
    airspace_frz_checked = db.Column(db.Boolean, default=False, nullable=False)
    airspace_restricted_checked = db.Column(db.Boolean, default=False, nullable=False)
    airspace_notams_reviewed = db.Column(db.Boolean, default=False, nullable=False)
    airspace_max_altitude_confirmed = db.Column(db.Boolean, default=False, nullable=False)
    airspace_planned_altitude = db.Column(db.Float, nullable=True)

    # ── 3. Weather Assessment (1 check + data) ────────────────────
    weather_acceptable = db.Column(db.Boolean, default=False, nullable=False)
    weather_wind_speed = db.Column(db.Float, nullable=True)
    weather_wind_direction = db.Column(db.String(50), nullable=True)
    weather_visibility = db.Column(db.Float, nullable=True)
    weather_precipitation = db.Column(db.String(50), nullable=True)
    weather_temperature = db.Column(db.Float, nullable=True)

    # ── 4. Equipment Check (6 checks + battery) ───────────────────
    equip_condition_ok = db.Column(db.Boolean, default=False, nullable=False)
    equip_battery_adequate = db.Column(db.Boolean, default=False, nullable=False)
    equip_battery_level = db.Column(db.Integer, nullable=True)
    equip_propellers_ok = db.Column(db.Boolean, default=False, nullable=False)
    equip_gps_lock = db.Column(db.Boolean, default=False, nullable=False)
    equip_remote_ok = db.Column(db.Boolean, default=False, nullable=False)
    equip_remote_id_active = db.Column(db.Boolean, default=False, nullable=False)

    # ── 5. IMSAFE Pilot Fitness (6 checks) ────────────────────────
    imsafe_illness = db.Column(db.Boolean, default=False, nullable=False)
    imsafe_medication = db.Column(db.Boolean, default=False, nullable=False)
    imsafe_stress = db.Column(db.Boolean, default=False, nullable=False)
    imsafe_alcohol = db.Column(db.Boolean, default=False, nullable=False)
    imsafe_fatigue = db.Column(db.Boolean, default=False, nullable=False)
    imsafe_eating = db.Column(db.Boolean, default=False, nullable=False)

    # ── 6. Permissions & Compliance (4 checks) ────────────────────
    perms_flyer_id_valid = db.Column(db.Boolean, default=False, nullable=False)
    perms_operator_id_displayed = db.Column(db.Boolean, default=False, nullable=False)
    perms_insurance_valid = db.Column(db.Boolean, default=False, nullable=False)
    perms_authorizations_checked = db.Column(db.Boolean, default=False, nullable=False)

    # ── 7. Emergency Procedures (3 checks) ────────────────────────
    emergency_landing_site = db.Column(db.Boolean, default=False, nullable=False)
    emergency_contacts_confirmed = db.Column(db.Boolean, default=False, nullable=False)
    emergency_contingency_plan = db.Column(db.Boolean, default=False, nullable=False)

    # ── Overall Decision ──────────────────────────────────────────
    risk_level = db.Column(db.String(20), nullable=False)
    decision = db.Column(db.String(30), nullable=False)
    mitigation_notes = db.Column(db.Text, nullable=True)
    pilot_declaration = db.Column(db.Boolean, default=False, nullable=False)
    gps_latitude = db.Column(db.Float, nullable=True)
    gps_longitude = db.Column(db.Float, nullable=True)

    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )

    # Relationships
    order = db.relationship(
        "Order", backref=db.backref("risk_assessment", uselist=False)
    )
    pilot = db.relationship("User", foreign_keys=[pilot_id])

    # All 28 mandatory boolean check fields
    CHECK_FIELDS = [
        # Site Assessment
        "site_ground_hazards", "site_obstacles_mapped",
        "site_50m_separation", "site_150m_residential",
        # Airspace Check
        "airspace_frz_checked", "airspace_restricted_checked",
        "airspace_notams_reviewed", "airspace_max_altitude_confirmed",
        # Weather Assessment
        "weather_acceptable",
        # Equipment Check
        "equip_condition_ok", "equip_battery_adequate",
        "equip_propellers_ok", "equip_gps_lock",
        "equip_remote_ok", "equip_remote_id_active",
        # IMSAFE
        "imsafe_illness", "imsafe_medication", "imsafe_stress",
        "imsafe_alcohol", "imsafe_fatigue", "imsafe_eating",
        # Permissions
        "perms_flyer_id_valid", "perms_operator_id_displayed",
        "perms_insurance_valid", "perms_authorizations_checked",
        # Emergency
        "emergency_landing_site", "emergency_contacts_confirmed",
        "emergency_contingency_plan",
    ]

    def all_checks_passed(self):
        return all(getattr(self, f) for f in self.CHECK_FIELDS)
