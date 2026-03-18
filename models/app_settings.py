from extensions import db


class AppSettings(db.Model):
    __tablename__ = "app_settings"

    id = db.Column(db.Integer, primary_key=True)

    # Branding
    business_name = db.Column(db.String(200), nullable=False, default="FlyingPlan")
    logo_url = db.Column(db.String(500), nullable=False, default="")
    primary_color = db.Column(db.String(7), nullable=False, default="#0d6efd")
    contact_email = db.Column(db.String(200), nullable=False, default="")
    tagline = db.Column(db.String(300), nullable=False, default="Drone Flight Brief")

    # Form field visibility toggles
    show_heard_about = db.Column(db.Boolean, nullable=False, default=True)
    show_customer_type_toggle = db.Column(db.Boolean, nullable=False, default=True)
    show_purpose_fields = db.Column(db.Boolean, nullable=False, default=True)
    show_output_format = db.Column(db.Boolean, nullable=False, default=True)

    # Guide mode (newbie tooltips / step-by-step instructions)
    guide_mode = db.Column(db.Boolean, nullable=False, default=True)

    # Dark mode
    dark_mode = db.Column(db.Boolean, nullable=False, default=False)

    @property
    def primary_color_rgb(self):
        c = self.primary_color or "#0d6efd"
        try:
            return f"{int(c[1:3],16)}, {int(c[3:5],16)}, {int(c[5:7],16)}"
        except (ValueError, IndexError):
            return "13, 110, 253"

    @staticmethod
    def get():
        """Return the singleton settings row, auto-creating if missing."""
        settings = db.session.get(AppSettings, 1)
        if not settings:
            settings = AppSettings(id=1)
            db.session.add(settings)
            db.session.commit()
        return settings

    def __repr__(self):
        return f"<AppSettings business_name={self.business_name}>"
