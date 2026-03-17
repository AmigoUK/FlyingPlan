from datetime import datetime, timezone
from extensions import db


class FlightPlan(db.Model):
    __tablename__ = "flight_plans"

    id = db.Column(db.Integer, primary_key=True)
    reference = db.Column(db.String(20), unique=True, nullable=False, index=True)
    status = db.Column(db.String(20), default="new", nullable=False)

    # Customer info
    customer_name = db.Column(db.String(200), nullable=False)
    customer_email = db.Column(db.String(200), nullable=False)
    customer_phone = db.Column(db.String(50))
    customer_company = db.Column(db.String(200))
    heard_about = db.Column(db.String(100))

    # Job brief
    job_type = db.Column(db.String(30), nullable=False)
    job_description = db.Column(db.Text)
    preferred_dates = db.Column(db.String(200))
    time_window = db.Column(db.String(100))
    urgency = db.Column(db.String(20), default="normal")
    special_requirements = db.Column(db.Text)

    # Location
    location_address = db.Column(db.String(500))
    location_lat = db.Column(db.Float)
    location_lng = db.Column(db.Float)
    area_polygon = db.Column(db.Text)  # JSON array of [lat, lng]
    estimated_area_sqm = db.Column(db.Float)

    # Flight prefs
    altitude_preset = db.Column(db.String(20))
    altitude_custom_m = db.Column(db.Float)
    camera_angle = db.Column(db.String(20))
    video_resolution = db.Column(db.String(10))
    photo_mode = db.Column(db.String(30))
    no_fly_notes = db.Column(db.Text)
    privacy_notes = db.Column(db.Text)

    # Customer type & business fields
    customer_type = db.Column(db.String(10), default="private")
    business_abn = db.Column(db.String(50))
    billing_contact = db.Column(db.String(200))
    billing_email = db.Column(db.String(200))
    purchase_order = db.Column(db.String(100))

    # Footage purpose & output
    footage_purpose = db.Column(db.String(50))
    footage_purpose_other = db.Column(db.String(300))
    output_format = db.Column(db.String(30))
    video_duration = db.Column(db.String(100))
    shot_types = db.Column(db.Text)  # JSON array
    delivery_timeline = db.Column(db.String(50))

    # Drone selection
    drone_model = db.Column(db.String(50), default="mini_4_pro", server_default="mini_4_pro")

    # Admin
    admin_notes = db.Column(db.Text)
    consent_given = db.Column(db.Boolean, nullable=False, default=False)
    created_at = db.Column(
        db.DateTime, default=lambda: datetime.now(timezone.utc)
    )
    updated_at = db.Column(
        db.DateTime,
        default=lambda: datetime.now(timezone.utc),
        onupdate=lambda: datetime.now(timezone.utc),
    )

    # Relationships
    pois = db.relationship("POI", backref="flight_plan", cascade="all, delete-orphan", lazy=True)
    waypoints = db.relationship("Waypoint", backref="flight_plan", cascade="all, delete-orphan", lazy=True, order_by="Waypoint.index")
    uploads = db.relationship("Upload", backref="flight_plan", cascade="all, delete-orphan", lazy=True)

    STATUSES = ["new", "in_review", "route_planned", "completed", "cancelled"]
    JOB_TYPES = ["aerial_photo", "inspection", "survey"]
    URGENCY_LEVELS = ["low", "normal", "high", "urgent"]

    def generate_reference(self):
        import random
        now = datetime.now(timezone.utc)
        date_part = now.strftime("%Y%m%d")
        rand_part = f"{random.randint(0, 9999):04d}"
        self.reference = f"FP-{date_part}-{rand_part}"
