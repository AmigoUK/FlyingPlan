"""Tests for v1.5 Grid/Area Mapping."""
import json
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from services.grid_generator import generate_grid


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


# Simple square polygon ~100m x 100m near London
SQUARE_POLYGON = [
    [51.5074, -0.1278],
    [51.5074, -0.1264],
    [51.5083, -0.1264],
    [51.5083, -0.1278],
]


def test_generate_parallel_grid():
    wps = generate_grid(SQUARE_POLYGON, {"spacing_m": 20, "angle_deg": 0})
    assert len(wps) > 0
    # Should have pairs of waypoints (start/end of each line)
    assert len(wps) % 2 == 0
    # All waypoints should be within polygon bounds roughly
    for wp in wps:
        assert 51.506 < wp["lat"] < 51.509
        assert -0.129 < wp["lng"] < -0.125


def test_generate_crosshatch():
    parallel_wps = generate_grid(SQUARE_POLYGON, {"spacing_m": 30, "pattern": "parallel"})
    cross_wps = generate_grid(SQUARE_POLYGON, {"spacing_m": 30, "pattern": "crosshatch"})
    # Crosshatch should have more waypoints (two passes)
    assert len(cross_wps) > len(parallel_wps)


def test_generate_with_angle():
    wps_0 = generate_grid(SQUARE_POLYGON, {"spacing_m": 20, "angle_deg": 0})
    wps_45 = generate_grid(SQUARE_POLYGON, {"spacing_m": 20, "angle_deg": 45})
    # Both should produce waypoints, but different positions
    assert len(wps_0) > 0
    assert len(wps_45) > 0


def test_grid_altitude_and_speed():
    wps = generate_grid(SQUARE_POLYGON, {"spacing_m": 30, "altitude_m": 50, "speed_ms": 8})
    assert all(w["altitude_m"] == 50 for w in wps)
    assert all(w["speed_ms"] == 8 for w in wps)


def test_empty_polygon():
    assert generate_grid([], {}) == []
    assert generate_grid([[1, 2]], {}) == []


def test_grid_indices_sequential():
    wps = generate_grid(SQUARE_POLYGON, {"spacing_m": 20})
    for i, wp in enumerate(wps):
        assert wp["index"] == i


def test_generate_grid_route(app):
    """Test admin generate-grid endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = FlightPlan(
            reference="FP-GRID-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=51.5074,
            location_lng=-0.1278,
            area_polygon=json.dumps(SQUARE_POLYGON),
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()

        resp = client.post(
            f"/admin/{fp.id}/generate-grid",
            json={"config": {"spacing_m": 30, "altitude_m": 40}},
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert data["count"] > 0
