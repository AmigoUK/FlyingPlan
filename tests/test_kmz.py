import io
import json
import zipfile
import xml.etree.ElementTree as ET
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
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
def flight_plan_with_waypoints(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260315-0001",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=-33.87,
            location_lng=151.21,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()

        wps = [
            Waypoint(
                flight_plan_id=fp.id, index=0,
                lat=-33.8688, lng=151.2093,
                altitude_m=30.0, speed_ms=5.0,
                gimbal_pitch_deg=-90.0,
                action_type="takePhoto",
            ),
            Waypoint(
                flight_plan_id=fp.id, index=1,
                lat=-33.8700, lng=151.2100,
                altitude_m=50.0, speed_ms=3.0,
                heading_deg=180.0,
                gimbal_pitch_deg=-45.0,
                hover_time_s=5.0,
                action_type="startRecord",
            ),
            Waypoint(
                flight_plan_id=fp.id, index=2,
                lat=-33.8710, lng=151.2110,
                altitude_m=40.0, speed_ms=4.0,
                gimbal_pitch_deg=-60.0,
                action_type="stopRecord",
            ),
        ]
        db.session.add_all(wps)
        db.session.commit()
        return fp


def test_kmz_is_valid_zip(app, flight_plan_with_waypoints):
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp)

        assert zipfile.is_zipfile(buf)
        buf.seek(0)
        with zipfile.ZipFile(buf) as zf:
            names = zf.namelist()
            assert "wpmz/template.kml" in names
            assert "wpmz/waylines.wpml" in names


def test_template_kml_structure(app, flight_plan_with_waypoints):
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp)
        buf.seek(0)

        with zipfile.ZipFile(buf) as zf:
            kml_content = zf.read("wpmz/template.kml").decode("UTF-8")

        root = ET.fromstring(kml_content)
        assert root.tag == "{%s}kml" % KML_NS

        # Find placemarks
        ns = {"kml": KML_NS, "wpml": WPML_NS}
        placemarks = root.findall(".//kml:Placemark", ns)
        assert len(placemarks) == 3

        # Check first waypoint coordinates (lng,lat format)
        coords = placemarks[0].find(".//kml:coordinates", ns)
        assert "151.2093" in coords.text
        assert "-33.8688" in coords.text


def test_waylines_wpml_structure(app, flight_plan_with_waypoints):
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp)
        buf.seek(0)

        with zipfile.ZipFile(buf) as zf:
            wpml_content = zf.read("wpmz/waylines.wpml").decode("UTF-8")

        root = ET.fromstring(wpml_content)
        ns = {"kml": KML_NS, "wpml": WPML_NS}

        # Check mission config
        mc = root.find(".//wpml:missionConfig", ns)
        assert mc is not None

        drone_enum = mc.find(".//wpml:droneEnumValue", ns)
        assert drone_enum.text == "68"

        # Check action groups exist
        action_groups = root.findall(".//wpml:actionGroup", ns)
        assert len(action_groups) == 3  # one per waypoint

        # Check hover action exists on WP 1
        placemarks = root.findall(".//kml:Placemark", ns)
        wp1_actions = placemarks[1].findall(".//wpml:action", ns)
        # Should have: gimbal rotate + hover + startRecord = 3
        assert len(wp1_actions) == 3


def test_coordinate_format(app, flight_plan_with_waypoints):
    """Verify KML uses lng,lat order (not lat,lng)."""
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp)
        buf.seek(0)

        with zipfile.ZipFile(buf) as zf:
            kml_content = zf.read("wpmz/template.kml").decode("UTF-8")

        root = ET.fromstring(kml_content)
        ns = {"kml": KML_NS, "wpml": WPML_NS}
        first_coords = root.find(".//kml:Placemark//kml:coordinates", ns).text

        # First number should be longitude (151.x), second latitude (-33.x)
        parts = first_coords.split(",")
        lng = float(parts[0])
        lat = float(parts[1])
        assert lng > 100  # longitude should be around 151
        assert lat < 0  # latitude should be around -33


def test_export_route(app, flight_plan_with_waypoints):
    """Test the admin export-kmz endpoint."""
    client = app.test_client()
    # Login
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        resp = client.get(f"/admin/{fp.id}/export-kmz")

    assert resp.status_code == 200
    assert resp.content_type == "application/vnd.google-earth.kmz"
    assert b"PK" in resp.data[:4]  # ZIP magic bytes
