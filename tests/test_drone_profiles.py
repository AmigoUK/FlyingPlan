"""Tests for v1.1 Multi-Drone Support."""
import io
import json
import zipfile
import xml.etree.ElementTree as ET
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from services.drone_profiles import DRONE_PROFILES, get_profile, get_choices, DEFAULT_DRONE
from services.kmz_generator import generate_kmz, WPML_NS, KML_NS


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
def flight_plan(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260317-0001",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
            drone_model="mavic_3",
        )
        db.session.add(fp)
        db.session.flush()
        wp = Waypoint(
            flight_plan_id=fp.id, index=0,
            lat=51.5074, lng=-0.1278,
            altitude_m=50.0, speed_ms=5.0,
            gimbal_pitch_deg=-90.0,
        )
        db.session.add(wp)
        db.session.commit()
        return fp


def test_all_profiles_have_required_keys():
    required = {"display_name", "droneEnumValue", "droneSubEnumValue",
                "payloadEnumValue", "max_altitude_m", "max_speed_ms",
                "max_wind_speed_ms", "max_flight_time_min",
                "sensor_width_mm", "sensor_height_mm", "focal_length_mm",
                "image_width_px", "image_height_px"}
    for model, profile in DRONE_PROFILES.items():
        missing = required - set(profile.keys())
        assert not missing, f"{model} missing keys: {missing}"


def test_get_profile_known():
    p = get_profile("mavic_3")
    assert p["droneEnumValue"] == 77
    assert p["display_name"] == "DJI Mavic 3"


def test_get_profile_unknown_falls_back():
    p = get_profile("unknown_drone")
    assert p == DRONE_PROFILES[DEFAULT_DRONE]


def test_get_choices_returns_tuples():
    choices = get_choices()
    assert len(choices) == len(DRONE_PROFILES)
    for key, name in choices:
        assert key in DRONE_PROFILES
        assert isinstance(name, str)


def test_kmz_uses_correct_drone_enum(app, flight_plan):
    """KMZ should use Mavic 3 enums when drone_model=mavic_3."""
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_kmz(fp)
        buf.seek(0)

        with zipfile.ZipFile(buf) as zf:
            wpml_content = zf.read("wpmz/waylines.wpml").decode("UTF-8")

        root = ET.fromstring(wpml_content)
        ns = {"kml": KML_NS, "wpml": WPML_NS}
        drone_enum = root.find(".//wpml:droneEnumValue", ns)
        assert drone_enum.text == "77"


def test_kmz_override_drone_model(app, flight_plan):
    """Can override drone model at export time."""
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_kmz(fp, drone_model="air_3")
        buf.seek(0)

        with zipfile.ZipFile(buf) as zf:
            wpml_content = zf.read("wpmz/waylines.wpml").decode("UTF-8")

        root = ET.fromstring(wpml_content)
        ns = {"kml": KML_NS, "wpml": WPML_NS}
        drone_enum = root.find(".//wpml:droneEnumValue", ns)
        assert drone_enum.text == "89"


def test_drone_model_default(app):
    """FlightPlan.drone_model defaults to mini_4_pro after insert."""
    with app.app_context():
        fp = FlightPlan(
            reference="FP-DEFAULT-TEST", customer_name="Test",
            customer_email="t@t.com", job_type="survey", consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()
        db.session.refresh(fp)
        assert fp.drone_model == "mini_4_pro"


def test_save_drone_model_route(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.post(
            f"/admin/{fp.id}/drone-model",
            json={"drone_model": "air_3s"},
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True

        db.session.refresh(fp)
        assert fp.drone_model == "air_3s"


def test_save_invalid_drone_model(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.post(
            f"/admin/{fp.id}/drone-model",
            json={"drone_model": "nonexistent"},
        )
        assert resp.status_code == 400
