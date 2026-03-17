"""
Terrain mesh generator for 3D mission preview.

Fetches an elevation grid for the mission bounding box and returns
it in a format suitable for Three.js terrain rendering.
"""
from services.elevation import get_elevations


def get_terrain_mesh(waypoints, resolution=20):
    """Fetch elevation grid for the bounding box of given waypoints.

    Args:
        waypoints: list of waypoint dicts (need lat, lng)
        resolution: number of samples per axis (default 20)

    Returns:
        dict with:
            elevations: 2D list [row][col] of elevation values
            bounds: { min_lat, max_lat, min_lng, max_lng }
            rows: number of rows
            cols: number of columns
            min_elevation: minimum elevation in the grid
            max_elevation: maximum elevation in the grid
    """
    if not waypoints:
        return {"elevations": [], "bounds": {}, "rows": 0, "cols": 0,
                "min_elevation": 0, "max_elevation": 0}

    lats = [w["lat"] for w in waypoints]
    lngs = [w["lng"] for w in waypoints]

    # Expand bounds by 10% for context
    lat_range = max(lats) - min(lats) or 0.001
    lng_range = max(lngs) - min(lngs) or 0.001
    margin = 0.1

    min_lat = min(lats) - lat_range * margin
    max_lat = max(lats) + lat_range * margin
    min_lng = min(lngs) - lng_range * margin
    max_lng = max(lngs) + lng_range * margin

    # Generate grid of sample points
    rows = resolution
    cols = resolution
    coords = []
    for r in range(rows):
        for c in range(cols):
            lat = min_lat + (max_lat - min_lat) * r / max(rows - 1, 1)
            lng = min_lng + (max_lng - min_lng) * c / max(cols - 1, 1)
            coords.append((lat, lng))

    # Fetch elevations
    elevs = get_elevations(coords)

    # Reshape to 2D grid
    grid = []
    idx = 0
    for r in range(rows):
        row = []
        for c in range(cols):
            row.append(elevs[idx] if elevs[idx] is not None else 0)
            idx += 1
        grid.append(row)

    flat = [v for row in grid for v in row]
    return {
        "elevations": grid,
        "bounds": {
            "min_lat": min_lat, "max_lat": max_lat,
            "min_lng": min_lng, "max_lng": max_lng,
        },
        "rows": rows,
        "cols": cols,
        "min_elevation": min(flat) if flat else 0,
        "max_elevation": max(flat) if flat else 0,
    }
