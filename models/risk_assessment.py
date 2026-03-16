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

    # ── Category Tracking ────────────────────────────────────────
    operational_category = db.Column(db.String(30))  # category at time of assessment
    category_version = db.Column(db.Integer, default=2)  # 1=legacy, 2=category-aware

    # ── Night Flying Checks (shown when time_of_day == night/twilight) ──
    night_green_light_fitted = db.Column(db.Boolean, default=False)
    night_green_light_on = db.Column(db.Boolean, default=False)
    night_vlos_maintainable = db.Column(db.Boolean, default=False)
    night_orientation_visible = db.Column(db.Boolean, default=False)

    # ── A2-specific checks (shown when category == open_a2) ─────
    a2_distance_confirmed = db.Column(db.Boolean, default=False)    # 30m normal / 5m low-speed / 50m legacy
    a2_low_speed_active = db.Column(db.Boolean, default=False)      # if using C2 low-speed reduction
    a2_segregation_assessed = db.Column(db.Boolean, default=False)  # area segregation evaluation

    # ── A3-specific checks (shown when category == open_a3) ─────
    a3_150m_from_areas = db.Column(db.Boolean, default=False)
    a3_50m_from_people = db.Column(db.Boolean, default=False)
    a3_50m_from_buildings = db.Column(db.Boolean, default=False)

    # ── Specific category checks (shown for PDRA-01 or SORA) ───
    specific_ops_manual_reviewed = db.Column(db.Boolean, default=False)
    specific_insurance_confirmed = db.Column(db.Boolean, default=False)
    specific_oa_valid = db.Column(db.Boolean, default=False)

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

    # Category-specific check fields mapping
    NIGHT_CHECK_FIELDS = [
        "night_green_light_fitted", "night_green_light_on",
        "night_vlos_maintainable", "night_orientation_visible",
    ]

    A2_CHECK_FIELDS = [
        "a2_distance_confirmed", "a2_low_speed_active", "a2_segregation_assessed",
    ]

    A3_CHECK_FIELDS = [
        "a3_150m_from_areas", "a3_50m_from_people", "a3_50m_from_buildings",
    ]

    SPECIFIC_CHECK_FIELDS = [
        "specific_ops_manual_reviewed", "specific_insurance_confirmed", "specific_oa_valid",
    ]

    CATEGORY_CHECKS = {
        'open_a1': [],  # base checks only
        'open_a2': A2_CHECK_FIELDS,
        'open_a3': A3_CHECK_FIELDS,
        'specific_pdra01': SPECIFIC_CHECK_FIELDS,
        'specific_sora': SPECIFIC_CHECK_FIELDS,
    }

    def all_checks_passed(self):
        return all(getattr(self, f) for f in self.CHECK_FIELDS)

    def get_required_checks(self):
        """Get all required check fields for this assessment's category."""
        checks = list(self.CHECK_FIELDS)
        if self.operational_category:
            checks.extend(self.CATEGORY_CHECKS.get(self.operational_category, []))
        return checks
