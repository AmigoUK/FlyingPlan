"""
UK airspace awareness service.
Serves airspace restriction data from bundled GeoJSON.
Checks if waypoints intersect restricted zones.
"""
import json
import os
from math import radians, cos, sin, sqrt, atan2


def get_airspace_geojson():
    """Load bundled UK airspace GeoJSON data."""
    geojson_path = os.path.join(
        os.path.dirname(os.path.dirname(__file__)),
        "static", "data", "uk_airspace.geojson"
    )
    if os.path.exists(geojson_path):
        with open(geojson_path, "r") as f:
            return json.load(f)
    return _generate_sample_airspace()


def check_waypoint_airspace(lat, lng, airspace_data=None):
    """Check if a waypoint is within any restricted airspace.

    Returns list of airspace zones the point is inside.
    """
    if airspace_data is None:
        airspace_data = get_airspace_geojson()

    violations = []
    features = airspace_data.get("features", [])

    for feature in features:
        props = feature.get("properties", {})
        geometry = feature.get("geometry", {})

        if geometry.get("type") == "Polygon":
            coords = geometry["coordinates"][0]
            if _point_in_polygon(lat, lng, coords):
                violations.append({
                    "name": props.get("name", "Unknown"),
                    "type": props.get("type", "unknown"),
                    "class": props.get("class", ""),
                    "upper_limit": props.get("upper_limit", ""),
                    "lower_limit": props.get("lower_limit", ""),
                })
        elif geometry.get("type") == "Point":
            center = geometry["coordinates"]
            radius_m = props.get("radius_m", 0)
            if radius_m > 0:
                dist = _haversine(lat, lng, center[1], center[0])
                if dist <= radius_m:
                    violations.append({
                        "name": props.get("name", "Unknown"),
                        "type": props.get("type", "unknown"),
                        "class": props.get("class", ""),
                        "distance_m": round(dist),
                    })

    return violations


def check_route_airspace(waypoints, airspace_data=None):
    """Check all waypoints against airspace restrictions.

    Returns dict mapping waypoint index to list of violations.
    """
    if airspace_data is None:
        airspace_data = get_airspace_geojson()

    results = {}
    for wp in waypoints:
        idx = wp.get("index", 0)
        violations = check_waypoint_airspace(wp["lat"], wp["lng"], airspace_data)
        if violations:
            results[idx] = violations

    return results


def _point_in_polygon(lat, lng, polygon_coords):
    """Ray casting algorithm for point-in-polygon test.
    polygon_coords: list of [lng, lat] (GeoJSON order).
    """
    n = len(polygon_coords)
    inside = False
    j = n - 1

    for i in range(n):
        xi, yi = polygon_coords[i][0], polygon_coords[i][1]
        xj, yj = polygon_coords[j][0], polygon_coords[j][1]

        if ((yi > lat) != (yj > lat)) and (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi):
            inside = not inside
        j = i

    return inside


def _haversine(lat1, lng1, lat2, lng2):
    """Calculate distance in metres between two points."""
    R = 6371000
    dlat = radians(lat2 - lat1)
    dlng = radians(lng2 - lng1)
    a = sin(dlat / 2) ** 2 + cos(radians(lat1)) * cos(radians(lat2)) * sin(dlng / 2) ** 2
    return R * 2 * atan2(sqrt(a), sqrt(1 - a))


def _generate_sample_airspace():
    """Generate sample UK airspace data for development/demo purposes."""
    return {
        "type": "FeatureCollection",
        "features": [
            {
                "type": "Feature",
                "properties": {
                    "name": "Heathrow FRZ",
                    "type": "FRZ",
                    "class": "prohibited",
                    "upper_limit": "FL060",
                    "lower_limit": "SFC",
                    "radius_m": 5000,
                },
                "geometry": {
                    "type": "Point",
                    "coordinates": [-0.4614, 51.4700],
                },
            },
            {
                "type": "Feature",
                "properties": {
                    "name": "Gatwick FRZ",
                    "type": "FRZ",
                    "class": "prohibited",
                    "upper_limit": "FL060",
                    "lower_limit": "SFC",
                    "radius_m": 5000,
                },
                "geometry": {
                    "type": "Point",
                    "coordinates": [-0.1903, 51.1537],
                },
            },
            {
                "type": "Feature",
                "properties": {
                    "name": "London CTR",
                    "type": "CTR",
                    "class": "controlled",
                    "upper_limit": "FL060",
                    "lower_limit": "SFC",
                },
                "geometry": {
                    "type": "Polygon",
                    "coordinates": [[
                        [-0.6, 51.3],
                        [0.2, 51.3],
                        [0.2, 51.7],
                        [-0.6, 51.7],
                        [-0.6, 51.3],
                    ]],
                },
            },
            {
                "type": "Feature",
                "properties": {
                    "name": "Birmingham International FRZ",
                    "type": "FRZ",
                    "class": "prohibited",
                    "upper_limit": "FL060",
                    "lower_limit": "SFC",
                    "radius_m": 5000,
                },
                "geometry": {
                    "type": "Point",
                    "coordinates": [-1.7478, 52.4539],
                },
            },
            {
                "type": "Feature",
                "properties": {
                    "name": "Manchester FRZ",
                    "type": "FRZ",
                    "class": "prohibited",
                    "upper_limit": "FL060",
                    "lower_limit": "SFC",
                    "radius_m": 5000,
                },
                "geometry": {
                    "type": "Point",
                    "coordinates": [-2.2750, 53.3537],
                },
            },
        ],
    }
