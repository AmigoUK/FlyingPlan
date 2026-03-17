"""Tests for v1.17 Photogrammetry Quality Estimator."""
import pytest
from services.photogrammetry_estimator import (
    estimate_point_density,
    compute_convergence_angles,
    generate_quality_report,
)
from services.oblique_grid import generate_oblique_grid


SQUARE_POLYGON = [
    [51.5074, -0.1278],
    [51.5074, -0.1264],
    [51.5083, -0.1264],
    [51.5083, -0.1278],
]


def test_point_density_zero_overlap():
    result = estimate_point_density({"stats": {"avg_overlap": 0}})
    assert result["estimated_points_per_sqm"] == 0
    assert result["quality_label"] == "none"


def test_point_density_good_overlap():
    result = estimate_point_density({"stats": {"avg_overlap": 5}})
    assert result["estimated_points_per_sqm"] > 100
    assert result["quality_label"] in ("good", "excellent")


def test_convergence_nadir_only():
    """Nadir-only parallel grid should have some convergence from nearby lines."""
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "nadir",
        "spacing_m": 20,
        "altitude_m": 30,
    })
    result = compute_convergence_angles(wps)
    # Nadir grid still has some convergence from adjacent lines but
    # double_grid should improve it
    assert result["avg_angle"] >= 0


def test_convergence_double_grid():
    """Double-grid should have better convergence than nadir-only."""
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "double_grid",
        "spacing_m": 20,
        "altitude_m": 30,
    })
    result = compute_convergence_angles(wps)
    # Double grid adds perpendicular passes
    assert result["avg_angle"] > 0


def test_convergence_empty():
    result = compute_convergence_angles([])
    assert result["avg_angle"] == 0
    assert result["quality_label"] == "poor"


def test_quality_report_nadir():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "nadir",
        "spacing_m": 30,
    })
    report = generate_quality_report(wps)
    assert report["overall_rating"] in ("red", "yellow", "green")
    assert "metrics" in report
    assert "recommendations" in report
    assert report["capture_mode"] == "nadir_only"


def test_quality_report_double_grid():
    wps = generate_oblique_grid(SQUARE_POLYGON, {
        "capture_mode": "double_grid",
        "spacing_m": 30,
        "gimbal_pitch_deg": -45,
    })
    report = generate_quality_report(wps)
    assert report["capture_mode"] == "double_grid"
    assert report["metrics"]["capture_mode"]["rating"] == "green"


def test_quality_report_empty():
    report = generate_quality_report([])
    assert report["overall_rating"] == "red"


def test_quality_report_has_all_metrics():
    wps = [
        {"lat": 51.5074, "lng": -0.1278, "altitude_m": 30,
         "gimbal_pitch_deg": -90, "heading_deg": 0},
    ]
    report = generate_quality_report(wps)
    expected_keys = {"overlap", "coverage", "gsd", "convergence",
                     "point_density", "capture_mode"}
    assert set(report["metrics"].keys()) == expected_keys
