"""
Facade scanning and structure inspection pattern generators.

Generates waypoints for:
- Single facade scan (vertical columns along a wall)
- Multi-face scan (all edges of a building polygon)
- Multi-altitude orbit (stacked orbits at different heights)
"""
import math
from services.geo_utils import offset_point, heading_to, haversine


def generate_facade_scan(face_start, face_end, config):
    """Generate waypoints for scanning a single facade (wall face).

    The drone flies vertical columns at standoff distance from the wall,
    with the camera pointing horizontally toward the structure.

    Args:
        face_start: [lat, lng] of wall start point
        face_end: [lat, lng] of wall end point
        config: dict with keys:
            standoff_m: distance from wall (default 10)
            column_spacing_m: horizontal spacing between columns (default 5)
            min_altitude_m: bottom of scan (default 10)
            max_altitude_m: top of scan (default 40)
            altitude_step_m: vertical step between rows (default 5)
            speed_ms: flight speed (default 3)
            action_type: camera action (default 'takePhoto')

    Returns:
        list of waypoint dicts with camera pointing at wall
    """
    standoff = config.get("standoff_m", 10)
    col_spacing = config.get("column_spacing_m", 5)
    min_alt = config.get("min_altitude_m", 10)
    max_alt = config.get("max_altitude_m", 40)
    alt_step = config.get("altitude_step_m", 5)
    speed_ms = config.get("speed_ms", 3)
    action_type = config.get("action_type", "takePhoto")

    # Calculate wall bearing and perpendicular offset direction
    wall_bearing_deg = heading_to(
        face_start[0], face_start[1],
        face_end[0], face_end[1]
    )
    wall_bearing_rad = math.radians(wall_bearing_deg)
    # Drone flies on the left side of the wall (perpendicular offset)
    perp_bearing_rad = wall_bearing_rad - math.pi / 2

    wall_length = haversine(
        face_start[0], face_start[1],
        face_end[0], face_end[1]
    )

    num_columns = max(1, int(wall_length / col_spacing) + 1)
    altitudes = []
    alt = min_alt
    while alt <= max_alt:
        altitudes.append(alt)
        alt += alt_step

    if not altitudes:
        altitudes = [min_alt]

    waypoints = []
    for col in range(num_columns):
        t = col / max(num_columns - 1, 1)
        # Point on the wall
        wall_lat = face_start[0] + t * (face_end[0] - face_start[0])
        wall_lng = face_start[1] + t * (face_end[1] - face_start[1])

        # Offset from wall by standoff distance
        drone_lat, drone_lng = offset_point(
            wall_lat, wall_lng, standoff, perp_bearing_rad
        )

        # Alternate column direction (bottom-up / top-down)
        col_alts = altitudes if col % 2 == 0 else list(reversed(altitudes))

        for alt_m in col_alts:
            # Gimbal pitch: horizontal (0) for facade, slight downward for higher positions
            rel_height = alt_m - (min_alt + max_alt) / 2
            gimbal = min(0, -math.degrees(math.atan2(rel_height, standoff)))
            gimbal = max(-90, round(gimbal, 1))

            # Camera faces the wall
            cam_heading = heading_to(drone_lat, drone_lng, wall_lat, wall_lng)

            waypoints.append({
                "index": len(waypoints),
                "lat": drone_lat,
                "lng": drone_lng,
                "altitude_m": alt_m,
                "speed_ms": speed_ms,
                "heading_deg": cam_heading,
                "gimbal_pitch_deg": gimbal,
                "turn_mode": "toPointAndStopWithDiscontinuityCurvature",
                "turn_damping_dist": 0.0,
                "hover_time_s": 0.0,
                "action_type": action_type,
                "poi_lat": wall_lat,
                "poi_lng": wall_lng,
            })

    return waypoints


def generate_multi_face_scan(building_polygon, config):
    """Generate facade scan for all edges of a building polygon.

    Args:
        building_polygon: list of [lat, lng] pairs defining building outline
        config: same as generate_facade_scan

    Returns:
        list of waypoint dicts scanning all facades
    """
    if not building_polygon or len(building_polygon) < 2:
        return []

    all_wps = []
    n = len(building_polygon)

    for i in range(n):
        start = building_polygon[i]
        end = building_polygon[(i + 1) % n]
        face_wps = generate_facade_scan(start, end, config)
        # Reindex
        offset = len(all_wps)
        for w in face_wps:
            w["index"] = w["index"] + offset
        all_wps.extend(face_wps)

    return all_wps


def generate_multi_altitude_orbit(center_lat, center_lng, config):
    """Generate stacked orbits at multiple altitudes around a point.

    Camera gimbal automatically adjusts per level to maintain view of target.

    Args:
        center_lat, center_lng: orbit center
        config: dict with keys:
            radius_m: orbit radius (default 30)
            min_altitude_m: lowest orbit (default 15)
            max_altitude_m: highest orbit (default 60)
            altitude_step_m: vertical spacing (default 15)
            num_points: points per orbit (default 12)
            speed_ms: flight speed (default 5)
            direction: 'cw' or 'ccw' (default 'cw')
            action_type: camera action (default 'takePhoto')

    Returns:
        list of waypoint dicts for stacked orbits
    """
    radius = config.get("radius_m", 30)
    min_alt = config.get("min_altitude_m", 15)
    max_alt = config.get("max_altitude_m", 60)
    alt_step = config.get("altitude_step_m", 15)
    num_points = int(config.get("num_points", 12))
    speed_ms = config.get("speed_ms", 5)
    direction = config.get("direction", "cw")
    action_type = config.get("action_type", "takePhoto")

    waypoints = []
    alt = min_alt
    while alt <= max_alt:
        # Auto-compute gimbal pitch: looking down at center from this height
        # Higher altitude = steeper look-down angle
        gimbal_pitch = -math.degrees(math.atan2(alt, radius))
        gimbal_pitch = max(-90, round(gimbal_pitch, 1))

        for i in range(num_points):
            if direction == "cw":
                angle = (2 * math.pi * i) / num_points
            else:
                angle = -(2 * math.pi * i) / num_points

            lat, lng = offset_point(center_lat, center_lng, radius, angle)
            hdg = heading_to(lat, lng, center_lat, center_lng)

            waypoints.append({
                "index": len(waypoints),
                "lat": lat,
                "lng": lng,
                "altitude_m": round(alt, 1),
                "speed_ms": speed_ms,
                "heading_deg": hdg,
                "gimbal_pitch_deg": gimbal_pitch,
                "turn_mode": "toPointAndPassWithContinuityCurvature",
                "turn_damping_dist": 0.0,
                "hover_time_s": 0.0,
                "action_type": action_type,
                "poi_lat": center_lat,
                "poi_lng": center_lng,
            })

        alt += alt_step

    return waypoints
