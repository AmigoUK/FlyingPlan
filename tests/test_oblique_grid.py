"""Tests for v1.13 Oblique Grid & Double-Grid Mission Planner."""
import json
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from services.oblique_grid import generate_oblique_grid
from services.geo_utils import (
    to_metres, to_latlng, rotate, offset_point, heading_to, haversine,
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


# Square polygon ~100m x 100m near London
SQUARE_POLYGON = [
    [51.5074, -0.1278],
    [51.5074, -0.1264],
    [51.5083, -0.1264],
    [51.5083, -0.1278],
]


# --- geo_utils tests ---

def test_to_metres_and_back():
    center_lat, center_lng = 51.5074, -0.1278
    x, y = to_metres(51.508, -0.127, center_lat, center_lng)
    lat, lng = to_latlng(x, y, center_lat, center_lng)
    assert abs(lat - 51.508) < 0.0001
    assert abs(lng - (-0.127)) < 0.0001


def test_rotate_360():
    import math
    x, y = rotate(10, 0, math.radians(360))
    assert abs(x - 10) < 0.001
    assert abs(y) < 0.001


def test_offset_point_north():
    lat, lng = offset_point(51.5, -0.1, 100, 0)  # 100m north
    assert lat > 51.5
    assert abs(lng - (-0.1)) < 0.001


def test_heading_to_north():
    h = heading_to(51.5, -0.1, 51.6, -0.1)
    assert abs(h - 0) < 1  # Should be roughly north (0 deg)


def test_heading_to_east():
    h = heading_to(51.5, -0.1, 51.5, 0.0)
    assert 85 < h < 95  # Should be roughly east (90 deg)


def test_haversine():
    d = haversine(51.5, -0.1, 51.5, -0.0986)
    assert 50 < d < 150  # Rough check: small distance


# --- Oblique grid tests ---

def test_nadir_mode_delegates():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "nadir",
        "spacing_m": 30,
    })
    assert len(wps) > 0
    # All should be nadir (-90)
    assert all(w["gimbal_pitch_deg"] == -90 for w in wps)


def test_oblique_mode():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "oblique",
        "spacing_m": 30,
        "gimbal_pitch_deg": -45,
        "heading_mode": "along_track",
    })
    assert len(wps) > 0
    assert all(w["gimbal_pitch_deg"] == -45 for w in wps)
    # Should have headings set
    has_headings = [w for w in wps if w["heading_deg"] is not None]
    assert len(has_headings) > 0


def test_double_grid_more_waypoints():
    nadir = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "nadir", "spacing_m": 30,
    })
    double = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "double_grid", "spacing_m": 30,
    })
    assert len(double) > len(nadir)


def test_double_grid_has_both_pitches():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "double_grid",
        "spacing_m": 30,
        "gimbal_pitch_deg": -45,
    })
    pitches = set(w["gimbal_pitch_deg"] for w in wps)
    assert -90 in pitches  # Nadir pass
    assert -45 in pitches  # Oblique pass


def test_multi_angle_five_passes():
    single = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "nadir", "spacing_m": 30,
    })
    multi = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "multi_angle", "spacing_m": 30,
    })
    # Multi-angle should have approximately 5x the waypoints
    assert len(multi) >= len(single) * 3  # At least 3x more


def test_indices_sequential():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "double_grid", "spacing_m": 30,
    })
    for i, wp in enumerate(wps):
        assert wp["index"] == i


def test_empty_polygon():
    assert generate_oblique_grid([], {}) == []
    assert generate_oblique_grid([[1, 2]], {}) == []


def test_fixed_heading_mode():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "oblique",
        "spacing_m": 30,
        "heading_mode": "fixed",
        "fixed_heading_deg": 180,
    })
    assert len(wps) > 0
    for w in wps:
        assert w["heading_deg"] == 180


def test_oblique_grid_route(app):
    """Test admin generate-oblique-grid endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = FlightPlan(
            reference="FP-OBLIQUE-TEST",
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
            f"/admin/{fp.id}/generate-oblique-grid",
            json={
                "polygon": SQUARE_POLYGON,
                "config": {
                    "capture_mode": "double_grid",
                    "spacing_m": 30,
                    "altitude_m": 50,
                },
            },
        )
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert data["count"] > 0
