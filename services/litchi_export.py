"""
Litchi CSV export for third-party flight app compatibility.

Litchi is a popular third-party DJI flight app that accepts CSV waypoint files.
Format: https://flylitchi.com/hub (CSV columns defined by Litchi spec).
"""
import io


def generate_litchi_csv(flight_plan):
    """Generate Litchi-compatible CSV from flight plan waypoints.

    Args:
        flight_plan: FlightPlan model with waypoints

    Returns:
        BytesIO buffer containing CSV data
    """
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    headers = [
        "latitude", "longitude", "altitude(m)", "heading(deg)",
        "curvesize(m)", "rotationdir", "gimbalmode",
        "gimbalpitchangle", "actiontype1", "actionparam1",
        "altitudemode", "speed(m/s)", "poi_latitude",
        "poi_longitude", "poi_altitude(m)", "poi_altitudemode",
        "photo_timeinterval", "photo_distinterval",
    ]

    lines = [",".join(headers)]

    for w in waypoints:
        heading = w.heading_deg if w.heading_deg is not None else 0
        gimbal_pitch = w.gimbal_pitch_deg if w.gimbal_pitch_deg is not None else -90

        # Litchi gimbal mode: 0=disabled, 1=focus_poi, 2=interpolate
        gimbal_mode = 2
        if w.poi_lat and w.poi_lng:
            gimbal_mode = 1

        # Map action types
        action_type = -1  # no action
        action_param = 0
        if w.action_type == "takePhoto":
            action_type = 1
            action_param = 0
        elif w.action_type == "startRecord":
            action_type = 2
            action_param = 0
        elif w.action_type == "stopRecord":
            action_type = 3
            action_param = 0

        # Curve size from turn mode
        curve_size = 0
        if w.turn_mode and "Pass" in w.turn_mode:
            curve_size = 5

        row = [
            f"{w.lat:.7f}",
            f"{w.lng:.7f}",
            f"{w.altitude_m:.1f}",
            f"{heading:.1f}",
            f"{curve_size}",
            "0",  # rotation dir: 0=CW
            f"{gimbal_mode}",
            f"{gimbal_pitch:.1f}",
            f"{action_type}",
            f"{action_param}",
            "1",  # altitude mode: 1=above takeoff
            f"{w.speed_ms:.1f}",
            f"{w.poi_lat:.7f}" if w.poi_lat else "0",
            f"{w.poi_lng:.7f}" if w.poi_lng else "0",
            "0",  # POI altitude
            "0",  # POI altitude mode
            "-1",  # photo time interval
            "-1",  # photo distance interval
        ]
        lines.append(",".join(row))

    buf = io.BytesIO("\n".join(lines).encode("utf-8"))
    buf.seek(0)
    return buf
