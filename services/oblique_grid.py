"""
Oblique grid and double-grid mission planner for 3D photogrammetry.

Capture modes:
- nadir: Standard straight-down grid (delegates to grid_generator)
- oblique: Single grid with angled camera (e.g., 45 deg)
- double_grid: Nadir + perpendicular oblique grid (industry standard for 3D)
- multi_angle: 5-pass capture (nadir + 4 oblique at 90 deg intervals)
"""
import math
from services.geo_utils import (
    to_metres, to_latlng, rotate, heading_to, offset_point,
)
from services.grid_generator import generate_grid


def generate_oblique_grid(polygon_coords, config):
    """Generate oblique grid waypoints for 3D photogrammetry.

    Args:
        polygon_coords: list of [lat, lng] pairs defining the polygon
        config: dict with keys:
            capture_mode: 'nadir', 'oblique', 'double_grid', 'multi_angle'
            spacing_m: line spacing in metres (default 20)
            angle_deg: grid direction in degrees from north (default 0)
            altitude_m: flight altitude (default 50)
            speed_ms: flight speed (default 5)
            gimbal_pitch_deg: camera pitch for oblique passes (default -45)
            heading_mode: 'along_track', 'fixed', 'poi' (default 'along_track')
            fixed_heading_deg: heading when heading_mode='fixed' (default 0)
            action_type: camera action (default 'takePhoto')

    Returns:
        list of waypoint dicts with computed headings and gimbal angles
    """
    if not polygon_coords or len(polygon_coords) < 3:
        return []

    mode = config.get("capture_mode", "nadir")
    spacing_m = config.get("spacing_m", 20)
    angle_deg = config.get("angle_deg", 0)
    altitude_m = config.get("altitude_m", 50)
    speed_ms = config.get("speed_ms", 5)
    oblique_pitch = config.get("gimbal_pitch_deg", -45)
    heading_mode = config.get("heading_mode", "along_track")
    fixed_heading = config.get("fixed_heading_deg", 0)
    action_type = config.get("action_type", "takePhoto")

    if mode == "nadir":
        return generate_grid(polygon_coords, {
            "spacing_m": spacing_m,
            "angle_deg": angle_deg,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "gimbal_pitch_deg": -90,
            "action_type": action_type,
        })

    elif mode == "oblique":
        wps = generate_grid(polygon_coords, {
            "spacing_m": spacing_m,
            "angle_deg": angle_deg,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "gimbal_pitch_deg": oblique_pitch,
            "action_type": action_type,
        })
        _apply_headings(wps, heading_mode, fixed_heading)
        return wps

    elif mode == "double_grid":
        # Pass 1: Nadir grid
        nadir_wps = generate_grid(polygon_coords, {
            "spacing_m": spacing_m,
            "angle_deg": angle_deg,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "gimbal_pitch_deg": -90,
            "action_type": action_type,
        })

        # Pass 2: Oblique grid perpendicular (angle + 90)
        oblique_wps = generate_grid(polygon_coords, {
            "spacing_m": spacing_m,
            "angle_deg": (angle_deg + 90) % 360,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "gimbal_pitch_deg": oblique_pitch,
            "action_type": action_type,
        })

        _apply_headings(oblique_wps, heading_mode, fixed_heading)

        # Reindex and combine
        offset = len(nadir_wps)
        for w in oblique_wps:
            w["index"] = w["index"] + offset
        nadir_wps.extend(oblique_wps)
        return nadir_wps

    elif mode == "multi_angle":
        all_wps = []
        # Pass 0: Nadir
        nadir_wps = generate_grid(polygon_coords, {
            "spacing_m": spacing_m,
            "angle_deg": angle_deg,
            "altitude_m": altitude_m,
            "speed_ms": speed_ms,
            "gimbal_pitch_deg": -90,
            "action_type": action_type,
        })
        all_wps.extend(nadir_wps)

        # Passes 1-4: Oblique at 0, 90, 180, 270 degrees
        for pass_angle in [0, 90, 180, 270]:
            grid_angle = (angle_deg + pass_angle) % 360
            pass_wps = generate_grid(polygon_coords, {
                "spacing_m": spacing_m,
                "angle_deg": grid_angle,
                "altitude_m": altitude_m,
                "speed_ms": speed_ms,
                "gimbal_pitch_deg": oblique_pitch,
                "action_type": action_type,
            })
            # Set heading along the flight direction for this pass
            for wp in pass_wps:
                wp["heading_deg"] = grid_angle
            offset = len(all_wps)
            for wp in pass_wps:
                wp["index"] = wp["index"] + offset
            all_wps.extend(pass_wps)

        return all_wps

    return []


def _apply_headings(waypoints, heading_mode, fixed_heading=0):
    """Apply heading computation to waypoints based on heading mode."""
    if heading_mode == "fixed":
        for wp in waypoints:
            wp["heading_deg"] = fixed_heading
    elif heading_mode == "along_track":
        # Compute heading from each waypoint to the next
        for i in range(len(waypoints)):
            if i < len(waypoints) - 1:
                wp = waypoints[i]
                next_wp = waypoints[i + 1]
                wp["heading_deg"] = heading_to(
                    wp["lat"], wp["lng"],
                    next_wp["lat"], next_wp["lng"]
                )
            elif i > 0:
                # Last waypoint: same heading as previous
                waypoints[i]["heading_deg"] = waypoints[i - 1]["heading_deg"]
