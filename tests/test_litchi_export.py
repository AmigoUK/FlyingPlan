"""Tests for v1.18 Litchi CSV export."""
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from services.litchi_export import generate_litchi_csv


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
            reference="FP-LITCHI-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()
        wps = [
            Waypoint(flight_plan_id=fp.id, index=0, lat=51.5074, lng=-0.1278,
                     altitude_m=30.0, speed_ms=5.0, heading_deg=90.0,
                     gimbal_pitch_deg=-45.0, action_type="takePhoto"),
            Waypoint(flight_plan_id=fp.id, index=1, lat=51.508, lng=-0.127,
                     altitude_m=40.0, speed_ms=4.0, heading_deg=180.0,
                     gimbal_pitch_deg=-90.0, poi_lat=51.5077, poi_lng=-0.1275),
        ]
        db.session.add_all(wps)
        db.session.commit()
        return fp


def test_litchi_csv_headers(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_litchi_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        assert "latitude" in lines[0]
        assert "longitude" in lines[0]
        assert "gimbalpitchangle" in lines[0]
        assert "gimbalmode" in lines[0]


def test_litchi_csv_row_count(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_litchi_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        assert len(lines) == 3  # header + 2 waypoints


def test_litchi_csv_values(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_litchi_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        fields = lines[1].split(",")
        assert fields[0] == "51.5074000"  # latitude
        assert fields[1] == "-0.1278000"  # longitude
        assert fields[2] == "30.0"  # altitude
        assert fields[3] == "90.0"  # heading


def test_litchi_csv_poi_gimbal_mode(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_litchi_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        # Second waypoint has POI, should use gimbal mode 1
        fields = lines[2].split(",")
        assert fields[6] == "1"  # gimbalmode = focus_poi


def test_litchi_export_route(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.get(f"/admin/{fp.id}/export-litchi")
        assert resp.status_code == 200
        assert b"latitude" in resp.data
