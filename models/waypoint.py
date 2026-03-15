from extensions import db


class Waypoint(db.Model):
    __tablename__ = "waypoints"

    id = db.Column(db.Integer, primary_key=True)
    flight_plan_id = db.Column(
        db.Integer, db.ForeignKey("flight_plans.id", ondelete="CASCADE"), nullable=False
    )
    index = db.Column(db.Integer, nullable=False)
    lat = db.Column(db.Float, nullable=False)
    lng = db.Column(db.Float, nullable=False)
    altitude_m = db.Column(db.Float, default=30.0)
    speed_ms = db.Column(db.Float, default=5.0)
    heading_deg = db.Column(db.Float, nullable=True)
    gimbal_pitch_deg = db.Column(db.Float, default=-90.0)
    turn_mode = db.Column(
        db.String(50), default="toPointAndStopWithDiscontinuityCurvature"
    )
    turn_damping_dist = db.Column(db.Float, default=0.0)
    hover_time_s = db.Column(db.Float, default=0.0)
    action_type = db.Column(db.String(30), nullable=True)
    poi_lat = db.Column(db.Float, nullable=True)
    poi_lng = db.Column(db.Float, nullable=True)

    def to_dict(self):
        return {
            "index": self.index,
            "lat": self.lat,
            "lng": self.lng,
            "altitude_m": self.altitude_m,
            "speed_ms": self.speed_ms,
            "heading_deg": self.heading_deg,
            "gimbal_pitch_deg": self.gimbal_pitch_deg,
            "turn_mode": self.turn_mode,
            "turn_damping_dist": self.turn_damping_dist,
            "hover_time_s": self.hover_time_s,
            "action_type": self.action_type,
            "poi_lat": self.poi_lat,
            "poi_lng": self.poi_lng,
        }
