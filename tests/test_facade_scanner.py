"""Tests for v1.14 Facade Scanning & Structure Inspection."""
import math
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from services.facade_scanner import (
    generate_facade_scan, generate_multi_face_scan,
    generate_multi_altitude_orbit,
)


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


# Simple east-west wall face near London
FACE_START = [51.5074, -0.1280]
FACE_END = [51.5074, -0.1260]

# Simple building rectangle
BUILDING = [
    [51.5074, -0.1280],
    [51.5074, -0.1260],
    [51.5080, -0.1260],
    [51.5080, -0.1280],
]


def test_facade_scan_generates_waypoints():
    wps = generate_facade_scan(FACE_START, FACE_END, {
        "standoff_m": 10,
        "column_spacing_m": 5,
        "min_altitude_m": 10,
        "max_altitude_m": 30,
        "altitude_step_m": 10,
    })
    assert len(wps) > 0


def test_facade_scan_has_poi():
    wps = generate_facade_scan(FACE_START, FACE_END, {
        "standoff_m": 10,
        "min_altitude_m": 10,
        "max_altitude_m": 20,
        "altitude_step_m": 10,
    })
    for wp in wps:
        assert wp["poi_lat"] is not None
        assert wp["poi_lng"] is not None


def test_facade_scan_altitude_range():
    wps = generate_facade_scan(FACE_START, FACE_END, {
        "standoff_m": 10,
        "min_altitude_m": 15,
        "max_altitude_m": 45,
        "altitude_step_m": 15,
    })
    alts = [w["altitude_m"] for w in wps]
    assert min(alts) == 15
    assert max(alts) == 45


def test_facade_scan_indices_sequential():
    wps = generate_facade_scan(FACE_START, FACE_END, {"standoff_m": 10})
    for i, wp in enumerate(wps):
        assert wp["index"] == i


def test_multi_face_scan():
    wps = generate_multi_face_scan(BUILDING, {
        "standoff_m": 10,
        "column_spacing_m": 10,
        "min_altitude_m": 10,
        "max_altitude_m": 20,
        "altitude_step_m": 10,
    })
    assert len(wps) > 0
    # Should have more waypoints than single face
    single = generate_facade_scan(FACE_START, FACE_END, {
        "standoff_m": 10,
        "column_spacing_m": 10,
        "min_altitude_m": 10,
        "max_altitude_m": 20,
        "altitude_step_m": 10,
    })
    assert len(wps) > len(single)


def test_multi_face_indices_sequential():
    wps = generate_multi_face_scan(BUILDING, {
        "standoff_m": 10, "column_spacing_m": 10,
    })
    for i, wp in enumerate(wps):
        assert wp["index"] == i


def test_multi_face_empty():
    assert generate_multi_face_scan([], {}) == []
    assert generate_multi_face_scan([[1, 2]], {}) == []


def test_multi_altitude_orbit():
    wps = generate_multi_altitude_orbit(51.5074, -0.1278, {
        "radius_m": 30,
        "min_altitude_m": 15,
        "max_altitude_m": 60,
        "altitude_step_m": 15,
        "num_points": 8,
    })
    assert len(wps) > 0
    # Should have 4 altitude levels * 8 points = 32
    assert len(wps) == 32


def test_multi_altitude_orbit_gimbal_varies():
    wps = generate_multi_altitude_orbit(51.5074, -0.1278, {
        "radius_m": 30,
        "min_altitude_m": 15,
        "max_altitude_m": 60,
        "altitude_step_m": 15,
        "num_points": 4,
    })
    pitches = set(w["gimbal_pitch_deg"] for w in wps)
    # Different altitudes should have different gimbal pitches
    assert len(pitches) > 1


def test_multi_altitude_orbit_has_poi():
    center_lat, center_lng = 51.5074, -0.1278
    wps = generate_multi_altitude_orbit(center_lat, center_lng, {
        "radius_m": 20, "num_points": 4,
    })
    for wp in wps:
        assert wp["poi_lat"] == center_lat
        assert wp["poi_lng"] == center_lng


def test_facade_scan_route(app):
    """Test admin generate-facade-scan endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = FlightPlan(
            reference="FP-FACADE-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="inspection",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()

        resp = client.post(
            f"/admin/{fp.id}/generate-facade-scan",
            json={
                "face_start": FACE_START,
                "face_end": FACE_END,
                "config": {
                    "standoff_m": 10,
                    "min_altitude_m": 10,
                    "max_altitude_m": 30,
                },
            },
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert data["count"] > 0


def test_multi_orbit_route(app):
    """Test admin generate-multi-orbit endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = FlightPlan(
            reference="FP-MORBIT-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="inspection",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()

        resp = client.post(
            f"/admin/{fp.id}/generate-multi-orbit",
            json={
                "config": {
                    "radius_m": 25,
                    "min_altitude_m": 15,
                    "max_altitude_m": 45,
                    "altitude_step_m": 15,
                    "num_points": 8,
                },
            },
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert data["count"] == 24  # 3 levels * 8 points
