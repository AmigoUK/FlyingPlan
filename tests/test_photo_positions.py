"""Tests for v1.18 Photo Positions export."""
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from services.photo_positions import generate_photo_positions_csv


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
            reference="FP-PHOTO-TEST",
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
                     gimbal_pitch_deg=-90.0),
            Waypoint(flight_plan_id=fp.id, index=1, lat=51.508, lng=-0.127,
                     altitude_m=40.0, speed_ms=4.0, heading_deg=180.0,
                     gimbal_pitch_deg=-45.0),
        ]
        db.session.add_all(wps)
        db.session.commit()
        return fp


def test_photo_positions_headers(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_photo_positions_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        assert "imageName" in lines[0]
        assert "omega" in lines[0]
        assert "phi" in lines[0]
        assert "kappa" in lines[0]


def test_photo_positions_row_count(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_photo_positions_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        assert len(lines) == 3  # header + 2 waypoints


def test_photo_positions_nadir_omega(app, flight_plan):
    """Nadir gimbal (-90) should produce omega=0."""
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_photo_positions_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        fields = lines[1].split(",")
        assert fields[0] == "IMG_0000.JPG"
        assert float(fields[4]) == 0.0  # omega for nadir


def test_photo_positions_oblique_omega(app, flight_plan):
    """Oblique gimbal (-45) should produce omega=45."""
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_photo_positions_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        fields = lines[2].split(",")
        assert float(fields[4]) == 45.0  # omega for -45 gimbal


def test_photo_positions_kappa(app, flight_plan):
    """Kappa should equal heading."""
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_photo_positions_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        fields = lines[1].split(",")
        assert float(fields[6]) == 90.0  # kappa = heading


def test_photo_positions_export_route(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.get(f"/admin/{fp.id}/export-photo-positions")
        assert resp.status_code == 200
        assert b"imageName" in resp.data
