"""
Grid/area mapping generator.
Takes a polygon + grid config and produces parallel flight lines (waypoints).

Algorithm:
1. Rotate polygon so scan lines are horizontal
2. Generate horizontal scan lines at spacing intervals
3. Clip each line to polygon boundary
4. Alternate direction for efficient lawn-mower pattern
5. Rotate back to original orientation
"""
import math


def generate_grid(polygon_coords, config):
    """Generate grid waypoints from a polygon area.

    Args:
        polygon_coords: list of [lat, lng] pairs defining the polygon
        config: dict with keys:
            spacing_m: line spacing in metres (default 20)
            angle_deg: grid direction in degrees from north (default 0 = N-S)
            altitude_m: flight altitude (default 30)
            speed_ms: flight speed (default 5)
            overlap_pct: side overlap percentage (default 70)
            pattern: 'parallel' or 'crosshatch' (default 'parallel')
            gimbal_pitch_deg: camera angle (default -90)
            action_type: camera action at each waypoint (default 'takePhoto')

    Returns:
        list of waypoint dicts
    """
    if not polygon_coords or len(polygon_coords) < 3:
        return []

    spacing_m = config.get("spacing_m", 20)
    angle_deg = config.get("angle_deg", 0)
    altitude_m = config.get("altitude_m", 30)
    speed_ms = config.get("speed_ms", 5)
    pattern = config.get("pattern", "parallel")
    gimbal_pitch = config.get("gimbal_pitch_deg", -90)
    action_type = config.get("action_type", "takePhoto")

    # Convert polygon to local metres (simple equirectangular projection)
    center_lat = sum(c[0] for c in polygon_coords) / len(polygon_coords)
    center_lng = sum(c[1] for c in polygon_coords) / len(polygon_coords)

    poly_m = [_to_metres(c[0], c[1], center_lat, center_lng) for c in polygon_coords]

    waypoints = _generate_scan_lines(poly_m, spacing_m, angle_deg, altitude_m,
                                     speed_ms, gimbal_pitch, action_type,
                                     center_lat, center_lng)

    if pattern == "crosshatch":
        cross_wps = _generate_scan_lines(poly_m, spacing_m, angle_deg + 90,
                                         altitude_m, speed_ms, gimbal_pitch,
                                         action_type, center_lat, center_lng)
        # Reindex cross waypoints
        offset = len(waypoints)
        for w in cross_wps:
            w["index"] = w["index"] + offset
        waypoints.extend(cross_wps)

    return waypoints


def _generate_scan_lines(poly_m, spacing_m, angle_deg, altitude_m, speed_ms,
                         gimbal_pitch, action_type, center_lat, center_lng):
    """Generate parallel scan lines across the polygon."""
    angle_rad = math.radians(angle_deg)

    # Rotate polygon so scan direction is vertical
    rotated = [_rotate(x, y, -angle_rad) for x, y in poly_m]

    # Find bounding box of rotated polygon
    xs = [p[0] for p in rotated]
    ys = [p[1] for p in rotated]
    min_x, max_x = min(xs), max(xs)
    min_y, max_y = min(ys), max(ys)

    waypoints = []
    line_idx = 0
    x = min_x + spacing_m / 2

    while x < max_x:
        # Find intersections of vertical line at x with polygon edges
        intersections = _line_polygon_intersections(x, rotated)

        if len(intersections) >= 2:
            intersections.sort()
            # Take pairs of intersections
            for i in range(0, len(intersections) - 1, 2):
                y_start = intersections[i]
                y_end = intersections[i + 1]

                # Alternate direction
                if line_idx % 2 == 1:
                    y_start, y_end = y_end, y_start

                # Rotate back and convert to lat/lng
                sx, sy = _rotate(x, y_start, angle_rad)
                ex, ey = _rotate(x, y_end, angle_rad)

                start_lat, start_lng = _to_latlng(sx, sy, center_lat, center_lng)
                end_lat, end_lng = _to_latlng(ex, ey, center_lat, center_lng)

                waypoints.append({
                    "index": len(waypoints),
                    "lat": start_lat,
                    "lng": start_lng,
                    "altitude_m": altitude_m,
                    "speed_ms": speed_ms,
                    "heading_deg": None,
                    "gimbal_pitch_deg": gimbal_pitch,
                    "turn_mode": "toPointAndPassWithContinuityCurvature",
                    "turn_damping_dist": 0.0,
                    "hover_time_s": 0.0,
                    "action_type": action_type,
                    "poi_lat": None,
                    "poi_lng": None,
                })
                waypoints.append({
                    "index": len(waypoints),
                    "lat": end_lat,
                    "lng": end_lng,
                    "altitude_m": altitude_m,
                    "speed_ms": speed_ms,
                    "heading_deg": None,
                    "gimbal_pitch_deg": gimbal_pitch,
                    "turn_mode": "toPointAndPassWithContinuityCurvature",
                    "turn_damping_dist": 0.0,
                    "hover_time_s": 0.0,
                    "action_type": action_type,
                    "poi_lat": None,
                    "poi_lng": None,
                })

            line_idx += 1

        x += spacing_m

    return waypoints


def _line_polygon_intersections(x, polygon):
    """Find y-coordinates where a vertical line x intersects polygon edges."""
    intersections = []
    n = len(polygon)
    for i in range(n):
        x1, y1 = polygon[i]
        x2, y2 = polygon[(i + 1) % n]

        if x1 == x2:
            continue

        if (x1 <= x <= x2) or (x2 <= x <= x1):
            t = (x - x1) / (x2 - x1)
            y = y1 + t * (y2 - y1)
            intersections.append(y)

    return intersections


def _rotate(x, y, angle_rad):
    """Rotate point around origin."""
    cos_a = math.cos(angle_rad)
    sin_a = math.sin(angle_rad)
    return x * cos_a - y * sin_a, x * sin_a + y * cos_a


def _to_metres(lat, lng, center_lat, center_lng):
    """Convert lat/lng to local metres (equirectangular projection)."""
    x = (lng - center_lng) * math.cos(math.radians(center_lat)) * 111320
    y = (lat - center_lat) * 110540
    return x, y


def _to_latlng(x, y, center_lat, center_lng):
    """Convert local metres back to lat/lng."""
    lat = center_lat + y / 110540
    lng = center_lng + x / (math.cos(math.radians(center_lat)) * 111320)
    return lat, lng
