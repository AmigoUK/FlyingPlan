"""
Pre-built mission pattern generators: orbit, spiral, cable cam.
Each generates waypoints with computed headings facing the subject.
"""
import math


def generate_orbit(center_lat, center_lng, radius_m=30, altitude_m=30,
                   num_points=12, speed_ms=5, direction="cw",
                   gimbal_pitch=-45, action_type="takePhoto"):
    """Generate circular orbit waypoints around a center point.

    Camera faces inward toward the center at all points.
    """
    waypoints = []
    for i in range(num_points):
        if direction == "cw":
            angle = (2 * math.pi * i) / num_points
        else:
            angle = -(2 * math.pi * i) / num_points

        lat, lng = _offset_point(center_lat, center_lng, radius_m, angle)
        heading = _heading_to(lat, lng, center_lat, center_lng)

        waypoints.append({
            "index": i,
            "lat": lat,
            "lng": lng,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "heading_deg": heading,
            "gimbal_pitch_deg": gimbal_pitch,
            "turn_mode": "toPointAndPassWithContinuityCurvature",
            "turn_damping_dist": 0.0,
            "hover_time_s": 0.0,
            "action_type": action_type,
            "poi_lat": center_lat,
            "poi_lng": center_lng,
        })

    return waypoints


def generate_spiral(center_lat, center_lng, radius_m=30,
                    start_altitude_m=20, end_altitude_m=60,
                    num_revolutions=3, points_per_rev=12,
                    speed_ms=4, direction="cw",
                    gimbal_pitch=-45, action_type="takePhoto"):
    """Generate ascending/descending spiral waypoints around a center.

    Good for tall structures — camera faces inward.
    """
    total_points = num_revolutions * points_per_rev
    alt_step = (end_altitude_m - start_altitude_m) / max(total_points - 1, 1)

    waypoints = []
    for i in range(total_points):
        if direction == "cw":
            angle = (2 * math.pi * i) / points_per_rev
        else:
            angle = -(2 * math.pi * i) / points_per_rev

        alt = start_altitude_m + alt_step * i
        lat, lng = _offset_point(center_lat, center_lng, radius_m, angle)
        heading = _heading_to(lat, lng, center_lat, center_lng)

        waypoints.append({
            "index": i,
            "lat": lat,
            "lng": lng,
            "altitude_m": round(alt, 1),
            "speed_ms": speed_ms,
            "heading_deg": heading,
            "gimbal_pitch_deg": gimbal_pitch,
            "turn_mode": "toPointAndPassWithContinuityCurvature",
            "turn_damping_dist": 0.0,
            "hover_time_s": 0.0,
            "action_type": action_type,
            "poi_lat": center_lat,
            "poi_lng": center_lng,
        })

    return waypoints


def generate_cable_cam(start_lat, start_lng, end_lat, end_lng,
                       altitude_m=30, num_points=10, speed_ms=3,
                       gimbal_pitch=-30, action_type="startRecord"):
    """Generate smooth two-point linear path (cable cam style).

    Heading follows the path direction.
    """
    waypoints = []
    heading = _heading_to(start_lat, start_lng, end_lat, end_lng)

    for i in range(num_points):
        t = i / max(num_points - 1, 1)
        lat = start_lat + t * (end_lat - start_lat)
        lng = start_lng + t * (end_lng - start_lng)

        wp_action = None
        if i == 0:
            wp_action = action_type
        elif i == num_points - 1 and action_type == "startRecord":
            wp_action = "stopRecord"

        waypoints.append({
            "index": i,
            "lat": lat,
            "lng": lng,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "heading_deg": heading,
            "gimbal_pitch_deg": gimbal_pitch,
            "turn_mode": "toPointAndPassWithContinuityCurvature",
            "turn_damping_dist": 0.0,
            "hover_time_s": 0.0,
            "action_type": wp_action,
            "poi_lat": None,
            "poi_lng": None,
        })

    return waypoints


def _offset_point(lat, lng, distance_m, bearing_rad):
    """Calculate a point at distance/bearing from origin (equirectangular)."""
    dx = distance_m * math.sin(bearing_rad)
    dy = distance_m * math.cos(bearing_rad)
    new_lat = lat + dy / 110540
    new_lng = lng + dx / (111320 * math.cos(math.radians(lat)))
    return round(new_lat, 7), round(new_lng, 7)


def _heading_to(from_lat, from_lng, to_lat, to_lng):
    """Calculate heading in degrees from one point to another."""
    dlng = math.radians(to_lng - from_lng)
    lat1 = math.radians(from_lat)
    lat2 = math.radians(to_lat)
    x = math.sin(dlng) * math.cos(lat2)
    y = math.cos(lat1) * math.sin(lat2) - math.sin(lat1) * math.cos(lat2) * math.cos(dlng)
    heading = math.degrees(math.atan2(x, y))
    return round(heading % 360, 1)
