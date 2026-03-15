from extensions import db


class POI(db.Model):
    __tablename__ = "pois"

    id = db.Column(db.Integer, primary_key=True)
    flight_plan_id = db.Column(
        db.Integer, db.ForeignKey("flight_plans.id", ondelete="CASCADE"), nullable=False
    )
    lat = db.Column(db.Float, nullable=False)
    lng = db.Column(db.Float, nullable=False)
    label = db.Column(db.String(200))
    sort_order = db.Column(db.Integer, default=0)
