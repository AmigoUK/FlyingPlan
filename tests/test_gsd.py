"""Tests for v1.10 GSD Calculator."""
from services.gsd_calculator import calculate_gsd, recommend_altitude


def test_gsd_basic():
    result = calculate_gsd("mini_4_pro", altitude_m=30)
    assert result["gsd_cm_per_px"] > 0
    assert result["footprint_width_m"] > 0
    assert result["line_spacing_m"] > 0
    assert "quality_tier" in result


def test_gsd_increases_with_altitude():
    gsd_low = calculate_gsd("mini_4_pro", altitude_m=20)
    gsd_high = calculate_gsd("mini_4_pro", altitude_m=100)
    assert gsd_high["gsd_cm_per_px"] > gsd_low["gsd_cm_per_px"]


def test_gsd_with_area():
    result = calculate_gsd("mini_4_pro", altitude_m=30, area_sqm=10000)
    assert "estimated_photos" in result
    assert result["estimated_photos"] > 0
    assert result["estimated_flight_time_min"] > 0
    assert result["batteries_needed"] >= 1


def test_gsd_different_drones():
    mini = calculate_gsd("mini_4_pro", altitude_m=50)
    mavic = calculate_gsd("mavic_3", altitude_m=50)
    # Mavic 3 has larger sensor = different GSD at same altitude
    assert mini["gsd_cm_per_px"] != mavic["gsd_cm_per_px"]


def test_recommend_altitude():
    alt = recommend_altitude("mini_4_pro", target_gsd_cm=2.0)
    assert alt > 0
    # Verify the recommended altitude achieves the target GSD
    result = calculate_gsd("mini_4_pro", altitude_m=alt)
    assert abs(result["gsd_cm_per_px"] - 2.0) < 0.5


def test_overlap_affects_spacing():
    low_overlap = calculate_gsd("mini_4_pro", altitude_m=30, overlap_pct=50)
    high_overlap = calculate_gsd("mini_4_pro", altitude_m=30, overlap_pct=80)
    assert high_overlap["line_spacing_m"] < low_overlap["line_spacing_m"]
