"""Tests for v1.16 3D Mission Preview terrain mesh."""
from unittest.mock import patch
from services.terrain_mesh import get_terrain_mesh


def _mock_elevations(coords):
    """Return fake elevations based on lat/lng for testing."""
    return [50 + (c[0] - 51.5) * 1000 for c in coords]


@patch("services.terrain_mesh.get_elevations", side_effect=_mock_elevations)
def test_terrain_mesh_basic(mock_elev):
    wps = [
        {"lat": 51.5074, "lng": -0.1278},
        {"lat": 51.508, "lng": -0.127},
    ]
    result = get_terrain_mesh(wps, resolution=5)
    assert result["rows"] == 5
    assert result["cols"] == 5
    assert len(result["elevations"]) == 5
    assert len(result["elevations"][0]) == 5
    assert "min_lat" in result["bounds"]
    assert result["min_elevation"] <= result["max_elevation"]


@patch("services.terrain_mesh.get_elevations", side_effect=_mock_elevations)
def test_terrain_mesh_empty(mock_elev):
    result = get_terrain_mesh([], resolution=5)
    assert result["rows"] == 0
    assert result["elevations"] == []


@patch("services.terrain_mesh.get_elevations", side_effect=_mock_elevations)
def test_terrain_mesh_bounds_expand(mock_elev):
    wps = [
        {"lat": 51.5074, "lng": -0.1278},
        {"lat": 51.508, "lng": -0.127},
    ]
    result = get_terrain_mesh(wps, resolution=3)
    bounds = result["bounds"]
    # Bounds should expand beyond waypoint range
    assert bounds["min_lat"] < 51.5074
    assert bounds["max_lat"] > 51.508


@patch("services.terrain_mesh.get_elevations", side_effect=_mock_elevations)
def test_terrain_mesh_resolution(mock_elev):
    wps = [
        {"lat": 51.5074, "lng": -0.1278},
        {"lat": 51.508, "lng": -0.127},
    ]
    r10 = get_terrain_mesh(wps, resolution=10)
    r20 = get_terrain_mesh(wps, resolution=20)
    assert r10["rows"] == 10
    assert r20["rows"] == 20
