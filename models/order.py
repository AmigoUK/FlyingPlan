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
    risk_assessment_completed = db.Column(db.Boolean, default=False, nullable=False)

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

    # Equipment selection (pilot picks which drone for this flight)
    equipment_id = db.Column(db.Integer, db.ForeignKey("pilot_equipment.id"), nullable=True)

    # Flight parameters (pilot fills pre-flight)
    time_of_day = db.Column(db.String(20))       # 'day','night','twilight'
    proximity_to_people = db.Column(db.String(30))  # 'over_uninvolved','near_under_50m','50m_plus','over_crowds','controlled_area'
    environment_type = db.Column(db.String(30))   # 'open_countryside','suburban','urban','industrial','congested'
    proximity_to_buildings = db.Column(db.String(20))  # 'over_150m','50_to_150m','under_50m'
    airspace_type = db.Column(db.String(20))      # 'uncontrolled','frz','controlled','restricted','danger'
    vlos_type = db.Column(db.String(20))          # 'vlos','extended_vlos','bvlos'
    speed_mode = db.Column(db.String(20))         # 'normal','low_speed','sport'

    # Category determination result (cached after engine runs)
    operational_category = db.Column(db.String(30))  # 'open_a1','open_a2','open_a3','specific_pdra01','specific_sora','certified'
    category_determined_at = db.Column(db.DateTime)
    category_blockers = db.Column(db.Text)  # JSON array of blocker strings

    TIMES_OF_DAY = ['day', 'night', 'twilight']
    PROXIMITY_TO_PEOPLE = ['over_uninvolved', 'near_under_50m', '50m_plus', 'over_crowds', 'controlled_area']
    ENVIRONMENT_TYPES = ['open_countryside', 'suburban', 'urban', 'industrial', 'congested']
    PROXIMITY_TO_BUILDINGS = ['over_150m', '50_to_150m', 'under_50m']
    AIRSPACE_TYPES = ['uncontrolled', 'frz', 'controlled', 'restricted', 'danger']
    VLOS_TYPES = ['vlos', 'extended_vlos', 'bvlos']
    SPEED_MODES = ['normal', 'low_speed', 'sport']

    flight_plan = db.relationship(
        "FlightPlan", backref=db.backref("order", uselist=False)
    )
    equipment = db.relationship("PilotEquipment", foreign_keys=[equipment_id])
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
