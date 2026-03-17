"""Tests for v1.15 Coverage Analysis & Overlap Heatmap."""
import math
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from services.coverage_analyzer import (
    compute_photo_footprint, compute_coverage_grid,
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


def test_nadir_footprint_is_rectangle():
    fp = compute_photo_footprint(
        51.5074, -0.1278, 30, -90, 0, "mini_4_pro"
    )
    assert len(fp) == 4
    # For nadir, footprint should be roughly symmetric
    lats = [p[0] for p in fp]
    lngs = [p[1] for p in fp]
    center_lat = sum(lats) / 4
    center_lng = sum(lngs) / 4
    assert abs(center_lat - 51.5074) < 0.001
    assert abs(center_lng - (-0.1278)) < 0.001


def test_oblique_footprint_is_trapezoid():
    nadir_fp = compute_photo_footprint(
        51.5074, -0.1278, 30, -90, 0, "mini_4_pro"
    )
    oblique_fp = compute_photo_footprint(
        51.5074, -0.1278, 30, -45, 0, "mini_4_pro"
    )
    # Oblique footprint should be larger than nadir
    nadir_area = _polygon_area(nadir_fp)
    oblique_area = _polygon_area(oblique_fp)
    assert oblique_area > nadir_area


def test_footprint_scales_with_altitude():
    low = compute_photo_footprint(51.5074, -0.1278, 20, -90, 0)
    high = compute_photo_footprint(51.5074, -0.1278, 60, -90, 0)
    assert _polygon_area(high) > _polygon_area(low)


def test_coverage_grid_basic():
    wps = [
        {"lat": 51.5074, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
        {"lat": 51.5076, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
    ]
    result = compute_coverage_grid(wps, resolution_m=5)
    assert result["rows"] > 0
    assert result["cols"] > 0
    assert len(result["grid"]) == result["rows"]
    assert result["stats"]["max_overlap"] >= 1


def test_coverage_grid_overlap():
    # Two nearby nadir waypoints should create overlap
    wps = [
        {"lat": 51.5074, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
        {"lat": 51.50745, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
    ]
    result = compute_coverage_grid(wps, resolution_m=3)
    assert result["stats"]["max_overlap"] >= 2


def test_coverage_grid_empty():
    result = compute_coverage_grid([], resolution_m=5)
    assert result["grid"] == []
    assert result["rows"] == 0


def test_coverage_stats():
    wps = [
        {"lat": 51.5074, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
    ]
    result = compute_coverage_grid(wps, resolution_m=5)
    stats = result["stats"]
    assert "min_overlap" in stats
    assert "max_overlap" in stats
    assert "avg_overlap" in stats
    assert "coverage_area_sqm" in stats
    assert "sufficient_pct" in stats


def test_coverage_analysis_route(app):
    """Test admin coverage-analysis endpoint."""
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})

    with app.app_context():
        fp = FlightPlan(
            reference="FP-COV-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()
        for i in range(3):
            db.session.add(Waypoint(
                flight_plan_id=fp.id, index=i,
                lat=51.5074 + i * 0.0002, lng=-0.1278,
                altitude_m=30.0, speed_ms=5.0, gimbal_pitch_deg=-90.0,
            ))
        db.session.commit()

        resp = client.post(f"/admin/{fp.id}/coverage-analysis", json={})
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert "stats" in data


def _polygon_area(coords):
    """Shoelace formula for polygon area (approximate)."""
    n = len(coords)
    area = 0
    for i in range(n):
        j = (i + 1) % n
        area += coords[i][0] * coords[j][1]
        area -= coords[j][0] * coords[i][1]
    return abs(area) / 2
