"""
Coverage analysis and overlap heatmap for photogrammetry missions.

Computes photo footprints (accounting for oblique angles) and rasterizes
them onto a grid to produce overlap count data.
"""
import math
from services.geo_utils import to_metres, to_latlng, rotate, offset_point
from services.drone_profiles import get_profile


def compute_photo_footprint(lat, lng, altitude_m, gimbal_pitch_deg,
                            heading_deg, drone_model="mini_4_pro"):
    """Compute the ground footprint polygon for a single photo.

    For nadir (gimbal=-90): returns a rectangle centered below the drone.
    For oblique: returns a trapezoid stretched in the viewing direction.

    Args:
        lat, lng: drone position
        altitude_m: flight altitude AGL
        gimbal_pitch_deg: camera pitch (-90=nadir, -45=oblique, 0=horizontal)
        heading_deg: camera heading in degrees (0=north)
        drone_model: drone profile key for camera specs

    Returns:
        list of 4 [lat, lng] corner points forming the footprint polygon
    """
    profile = get_profile(drone_model)
    sensor_w = profile["sensor_width_mm"]
    sensor_h = profile["sensor_height_mm"]
    focal = profile["focal_length_mm"]

    pitch_rad = math.radians(gimbal_pitch_deg)
    heading_rad = math.radians(heading_deg or 0)

    if gimbal_pitch_deg <= -89:
        # Nadir: simple rectangle
        half_w = altitude_m * (sensor_w / 2) / focal
        half_h = altitude_m * (sensor_h / 2) / focal
        corners_local = [
            (-half_w, -half_h),
            (half_w, -half_h),
            (half_w, half_h),
            (-half_w, half_h),
        ]
    else:
        # Oblique: trapezoid
        # Camera looks at angle from vertical
        view_angle = math.pi / 2 + pitch_rad  # angle from horizontal

        if view_angle <= 0.05:
            # Nearly horizontal — footprint extends very far, cap it
            view_angle = 0.05

        # Near and far ground distances from nadir point
        half_fov_v = math.atan2(sensor_h / 2, focal)
        half_fov_h = math.atan2(sensor_w / 2, focal)

        # Angles from vertical to near and far edges
        near_angle = view_angle + half_fov_v
        far_angle = view_angle - half_fov_v

        # Ground distances
        if near_angle >= math.pi / 2:
            near_dist = altitude_m * 10  # cap
        else:
            near_dist = altitude_m * math.tan(near_angle)

        if far_angle >= math.pi / 2:
            far_dist = altitude_m * 10
        elif far_angle <= 0:
            far_dist = altitude_m * 0.5
        else:
            far_dist = altitude_m * math.tan(far_angle)

        # Width at near and far edges
        near_half_w = near_dist * math.tan(half_fov_h)
        far_half_w = far_dist * math.tan(half_fov_h)

        # Corners in local frame (x=right, y=forward in viewing direction)
        corners_local = [
            (-far_half_w, far_dist),
            (far_half_w, far_dist),
            (near_half_w, near_dist),
            (-near_half_w, near_dist),
        ]

    # Rotate by heading and convert to lat/lng
    footprint = []
    for x, y in corners_local:
        rx, ry = rotate(x, y, -heading_rad)
        fp_lat, fp_lng = to_latlng(rx, ry, lat, lng)
        footprint.append([fp_lat, fp_lng])

    return footprint


def compute_coverage_grid(waypoints, drone_model="mini_4_pro", resolution_m=5):
    """Rasterize all photo footprints onto a grid and count overlaps.

    Args:
        waypoints: list of waypoint dicts (need lat, lng, altitude_m,
                   gimbal_pitch_deg, heading_deg)
        drone_model: drone profile key
        resolution_m: grid cell size in metres

    Returns:
        dict with:
            grid: 2D list of overlap counts [row][col]
            bounds: { min_lat, max_lat, min_lng, max_lng }
            resolution_m: cell size
            stats: { min_overlap, max_overlap, avg_overlap,
                     coverage_area_sqm, sufficient_pct }
            rows: number of rows
            cols: number of columns
    """
    if not waypoints:
        return {"grid": [], "bounds": {}, "resolution_m": resolution_m,
                "stats": {}, "rows": 0, "cols": 0}

    # Compute all footprints
    footprints = []
    for wp in waypoints:
        fp = compute_photo_footprint(
            wp["lat"], wp["lng"],
            wp.get("altitude_m", 30),
            wp.get("gimbal_pitch_deg", -90),
            wp.get("heading_deg", 0),
            drone_model,
        )
        footprints.append(fp)

    if not footprints:
        return {"grid": [], "bounds": {}, "resolution_m": resolution_m,
                "stats": {}, "rows": 0, "cols": 0}

    # Find bounding box of all footprints
    all_lats = [p[0] for fp in footprints for p in fp]
    all_lngs = [p[1] for fp in footprints for p in fp]
    min_lat = min(all_lats)
    max_lat = max(all_lats)
    min_lng = min(all_lngs)
    max_lng = max(all_lngs)

    center_lat = (min_lat + max_lat) / 2
    center_lng = (min_lng + max_lng) / 2

    # Convert bounds to metres for grid sizing
    min_x, min_y = _to_m(min_lat, min_lng, center_lat, center_lng)
    max_x, max_y = _to_m(max_lat, max_lng, center_lat, center_lng)

    cols = max(1, int((max_x - min_x) / resolution_m) + 1)
    rows = max(1, int((max_y - min_y) / resolution_m) + 1)

    # Cap grid size to avoid memory issues
    if rows * cols > 100000:
        scale = math.sqrt(rows * cols / 100000)
        resolution_m = resolution_m * scale
        cols = max(1, int((max_x - min_x) / resolution_m) + 1)
        rows = max(1, int((max_y - min_y) / resolution_m) + 1)

    grid = [[0] * cols for _ in range(rows)]

    # Convert footprints to local metres
    for fp in footprints:
        fp_m = [_to_m(p[0], p[1], center_lat, center_lng) for p in fp]
        # Rasterize: check each grid cell against the footprint polygon
        fp_lats = [p[1] for p in fp_m]
        fp_lngs = [p[0] for p in fp_m]
        fp_min_r = max(0, int((min(fp_lats) - min_y) / resolution_m))
        fp_max_r = min(rows - 1, int((max(fp_lats) - min_y) / resolution_m))
        fp_min_c = max(0, int((min(fp_lngs) - min_x) / resolution_m))
        fp_max_c = min(cols - 1, int((max(fp_lngs) - min_x) / resolution_m))

        for r in range(fp_min_r, fp_max_r + 1):
            for c in range(fp_min_c, fp_max_c + 1):
                cx = min_x + (c + 0.5) * resolution_m
                cy = min_y + (r + 0.5) * resolution_m
                if _point_in_quad(cx, cy, fp_m):
                    grid[r][c] += 1

    # Compute stats
    nonzero = [grid[r][c] for r in range(rows) for c in range(cols)
               if grid[r][c] > 0]
    total_cells = rows * cols
    covered_cells = len(nonzero)
    sufficient = sum(1 for v in nonzero if v >= 3)

    stats = {
        "min_overlap": min(nonzero) if nonzero else 0,
        "max_overlap": max(nonzero) if nonzero else 0,
        "avg_overlap": round(sum(nonzero) / len(nonzero), 1) if nonzero else 0,
        "coverage_area_sqm": round(covered_cells * resolution_m * resolution_m, 1),
        "sufficient_pct": round(100 * sufficient / covered_cells, 1) if covered_cells else 0,
    }

    return {
        "grid": grid,
        "bounds": {
            "min_lat": min_lat, "max_lat": max_lat,
            "min_lng": min_lng, "max_lng": max_lng,
        },
        "resolution_m": resolution_m,
        "stats": stats,
        "rows": rows,
        "cols": cols,
    }


def _to_m(lat, lng, center_lat, center_lng):
    """Quick wrapper for to_metres."""
    x = (lng - center_lng) * math.cos(math.radians(center_lat)) * 111320
    y = (lat - center_lat) * 110540
    return x, y


def _point_in_quad(x, y, quad):
    """Check if point (x,y) is inside a quadrilateral using ray casting."""
    n = len(quad)
    inside = False
    j = n - 1
    for i in range(n):
        xi, yi = quad[i]
        xj, yj = quad[j]
        if ((yi > y) != (yj > y)) and (x < (xj - xi) * (y - yi) / (yj - yi) + xi):
            inside = not inside
        j = i
    return inside
