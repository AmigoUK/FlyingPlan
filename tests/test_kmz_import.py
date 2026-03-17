"""Tests for v1.3 KMZ Import."""
import io
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from services.kmz_generator import generate_kmz
from services.kmz_parser import parse_kmz


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
            reference="FP-20260317-0002",
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
        wps = [
            Waypoint(flight_plan_id=fp.id, index=0, lat=51.5074, lng=-0.1278,
                     altitude_m=50.0, speed_ms=5.0, gimbal_pitch_deg=-90.0,
                     action_type="takePhoto"),
            Waypoint(flight_plan_id=fp.id, index=1, lat=51.5080, lng=-0.1270,
                     altitude_m=60.0, speed_ms=3.0, heading_deg=180.0,
                     gimbal_pitch_deg=-45.0, hover_time_s=5.0),
            Waypoint(flight_plan_id=fp.id, index=2, lat=51.5090, lng=-0.1260,
                     altitude_m=40.0, speed_ms=4.0, gimbal_pitch_deg=-60.0),
        ]
        db.session.add_all(wps)
        db.session.commit()
        return fp


def test_roundtrip_export_import(app, flight_plan_with_waypoints):
    """Export KMZ then import it — waypoints should survive the roundtrip."""
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp)
        result = parse_kmz(buf.read())

        assert result["error"] is None
        assert len(result["waypoints"]) == 3
        assert result["drone_model"] == "mavic_3"

        wp0 = result["waypoints"][0]
        assert abs(wp0["lat"] - 51.5074) < 0.001
        assert abs(wp0["lng"] - (-0.1278)) < 0.001
        assert wp0["altitude_m"] == 50.0
        assert wp0["speed_ms"] == 5.0


def test_parse_detects_drone_model(app, flight_plan_with_waypoints):
    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        buf = generate_kmz(fp, drone_model="air_3")
        result = parse_kmz(buf.read())
        assert result["drone_model"] == "air_3"


def test_parse_invalid_file():
    result = parse_kmz(b"not a zip file")
    assert result["error"] is not None
    assert result["waypoints"] == []


def test_parse_empty_zip():
    import zipfile
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, "w") as zf:
        zf.writestr("random.txt", "nothing")
    result = parse_kmz(buf.getvalue())
    assert "No wpmz/" in result["error"]


def test_import_route(app, flight_plan_with_waypoints):
    """Test the admin import-kmz endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = db.session.merge(flight_plan_with_waypoints)
        # Generate a KMZ to import
        buf = generate_kmz(fp, drone_model="air_3s")
        kmz_bytes = buf.read()

        # Create a new empty flight plan to import into
        fp2 = FlightPlan(
            reference="FP-20260317-IMPORT",
            customer_name="Importer",
            customer_email="i@i.com",
            job_type="inspection",
            location_lat=51.0,
            location_lng=-0.1,
            consent_given=True,
        )
        db.session.add(fp2)
        db.session.commit()

        resp = client.post(
            f"/admin/{fp2.id}/import-kmz",
            data={"kmz_file": (io.BytesIO(kmz_bytes), "test.kmz")},
            content_type="multipart/form-data",
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert data["count"] == 3
        assert data["drone_model"] == "air_3s"

        # Verify waypoints were saved
        wps = Waypoint.query.filter_by(flight_plan_id=fp2.id).order_by(Waypoint.index).all()
        assert len(wps) == 3
