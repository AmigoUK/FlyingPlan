"""
Terrain-following mode: adjusts waypoint altitudes to maintain constant AGL.
Adds intermediate waypoints along segments where terrain changes significantly.
"""
from services.elevation import get_elevations, get_path_elevations


def apply_terrain_following(waypoints, target_agl_m=30, interpolation_samples=5):
    """Adjust waypoints to maintain constant AGL above terrain.

    Args:
        waypoints: list of waypoint dicts
        target_agl_m: desired height above ground level
        interpolation_samples: intermediate points per segment

    Returns:
        list of adjusted waypoint dicts (may include interpolated points)
    """
    if not waypoints:
        return []

    # Get elevation at each original waypoint
    coords = [(w["lat"], w["lng"]) for w in waypoints]
    elevations = get_elevations(coords)

    adjusted = []
    wp_index = 0

    for i, (wp, ground_elev) in enumerate(zip(waypoints, elevations)):
        ground_elev = ground_elev or 0

        # Add the original waypoint with adjusted altitude
        adj = dict(wp)
        adj["index"] = wp_index
        adj["altitude_m"] = ground_elev + target_agl_m
        adj["ground_elevation_m"] = ground_elev
        adj["agl_m"] = target_agl_m
        adjusted.append(adj)
        wp_index += 1

        # Add intermediate points between this and next waypoint
        if i < len(waypoints) - 1 and interpolation_samples > 0:
            next_wp = waypoints[i + 1]
            path_data = get_path_elevations(
                wp["lat"], wp["lng"],
                next_wp["lat"], next_wp["lng"],
                num_samples=interpolation_samples + 2,
            )

            # Skip first and last (they are the original waypoints)
            for pd in path_data[1:-1]:
                interp = dict(wp)
                interp["index"] = wp_index
                interp["lat"] = pd["lat"]
                interp["lng"] = pd["lng"]
                interp["altitude_m"] = (pd["elevation"] or 0) + target_agl_m
                interp["ground_elevation_m"] = pd["elevation"] or 0
                interp["agl_m"] = target_agl_m
                interp["action_type"] = None  # No action at intermediate points
                interp["hover_time_s"] = 0.0
                adjusted.append(interp)
                wp_index += 1

    return adjusted
