import pytest
from app import create_app
from extensions import db
from models.user import User
from models.flight_plan import FlightPlan
from models.poi import POI
from models.waypoint import Waypoint


@pytest.fixture
def app():
    class TestConfig:
        SQLALCHEMY_DATABASE_URI = "sqlite:///:memory:"
        TESTING = True
        SECRET_KEY = "test"
        WTF_CSRF_ENABLED = False
        UPLOAD_FOLDER = "/tmp/fp_test_uploads"
        MAX_CONTENT_LENGTH = 32 * 1024 * 1024

    app = create_app(TestConfig)
    with app.app_context():
        yield app


@pytest.fixture
def client(app):
    return app.test_client()


def test_user_password(app):
    with app.app_context():
        u = User(username="test", display_name="Test")
        u.set_password("hello123")
        assert u.check_password("hello123")
        assert not u.check_password("wrong")


def test_user_seeded(app):
    with app.app_context():
        admin = User.query.filter_by(username="admin").first()
        assert admin is not None
        assert admin.check_password("admin123")


def test_flight_plan_reference(app):
    with app.app_context():
        fp = FlightPlan(
            customer_name="Test",
            customer_email="t@t.com",
            job_type="inspection",
            consent_given=True,
        )
        fp.generate_reference()
        assert fp.reference.startswith("FP-")
        assert len(fp.reference) == 16  # FP-YYYYMMDD-XXXX


def test_flight_plan_relationships(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260315-0001",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()

        poi = POI(flight_plan_id=fp.id, lat=-33.86, lng=151.2, label="Test POI")
        wp = Waypoint(flight_plan_id=fp.id, index=0, lat=-33.86, lng=151.2)
        db.session.add_all([poi, wp])
        db.session.commit()

        assert len(fp.pois) == 1
        assert len(fp.waypoints) == 1
        assert fp.pois[0].label == "Test POI"


def test_waypoint_to_dict(app):
    with app.app_context():
        wp = Waypoint(
            flight_plan_id=1, index=0,
            lat=-33.86, lng=151.2,
            altitude_m=50.0, speed_ms=3.0,
        )
        d = wp.to_dict()
        assert d["lat"] == -33.86
        assert d["altitude_m"] == 50.0
        assert d["speed_ms"] == 3.0
        assert d["index"] == 0


def test_cascade_delete(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260315-0002",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="aerial_photo",
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()

        poi = POI(flight_plan_id=fp.id, lat=-33.86, lng=151.2)
        wp = Waypoint(flight_plan_id=fp.id, index=0, lat=-33.86, lng=151.2)
        db.session.add_all([poi, wp])
        db.session.commit()

        db.session.delete(fp)
        db.session.commit()

        assert POI.query.count() == 0
        assert Waypoint.query.count() == 0
