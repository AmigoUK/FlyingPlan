"""Tests for v1.9 Mission Patterns."""
import math
from services.mission_patterns import generate_orbit, generate_spiral, generate_cable_cam


def test_orbit_generates_correct_count():
    wps = generate_orbit(51.5, -0.1, num_points=8)
    assert len(wps) == 8


def test_orbit_headings_face_center():
    center_lat, center_lng = 51.5, -0.1
    wps = generate_orbit(center_lat, center_lng, radius_m=50, num_points=4)
    for wp in wps:
        assert wp["heading_deg"] is not None
        assert wp["poi_lat"] == center_lat
        assert wp["poi_lng"] == center_lng


def test_orbit_indices_sequential():
    wps = generate_orbit(51.5, -0.1, num_points=12)
    for i, wp in enumerate(wps):
        assert wp["index"] == i


def test_spiral_ascending():
    wps = generate_spiral(51.5, -0.1, start_altitude_m=10, end_altitude_m=50,
                          num_revolutions=2, points_per_rev=6)
    assert len(wps) == 12
    # Altitudes should generally increase
    assert wps[0]["altitude_m"] < wps[-1]["altitude_m"]


def test_cable_cam_linear():
    wps = generate_cable_cam(51.5, -0.1, 51.501, -0.099, num_points=5)
    assert len(wps) == 5
    # Start and end should match
    assert abs(wps[0]["lat"] - 51.5) < 0.001
    assert abs(wps[-1]["lat"] - 51.501) < 0.001
    # All headings should be the same
    headings = [wp["heading_deg"] for wp in wps]
    assert all(h == headings[0] for h in headings)


def test_cable_cam_recording_actions():
    wps = generate_cable_cam(51.5, -0.1, 51.501, -0.099, num_points=5,
                             action_type="startRecord")
    assert wps[0]["action_type"] == "startRecord"
    assert wps[-1]["action_type"] == "stopRecord"
    assert wps[2]["action_type"] is None
