"""
Terrain elevation service using Open-Meteo Elevation API.
Free, no API key required: api.open-meteo.com/v1/elevation
"""
import requests

ELEVATION_API_URL = "https://api.open-meteo.com/v1/elevation"
_cache = {}


def get_elevations(coordinates):
    """Get ground elevation for a list of (lat, lng) tuples.

    Args:
        coordinates: list of (lat, lng) tuples

    Returns:
        list of elevation values in metres (or None if API fails)
    """
    if not coordinates:
        return []

    # Check cache first
    uncached = []
    uncached_indices = []
    results = [None] * len(coordinates)

    for i, (lat, lng) in enumerate(coordinates):
        key = (round(lat, 5), round(lng, 5))
        if key in _cache:
            results[i] = _cache[key]
        else:
            uncached.append((lat, lng))
            uncached_indices.append(i)

    if not uncached:
        return results

    # Batch API call (max 100 at a time)
    batch_size = 100
    for start in range(0, len(uncached), batch_size):
        batch = uncached[start:start + batch_size]
        lats = ",".join(f"{lat:.5f}" for lat, _ in batch)
        lngs = ",".join(f"{lng:.5f}" for _, lng in batch)

        try:
            resp = requests.get(
                ELEVATION_API_URL,
                params={"latitude": lats, "longitude": lngs},
                timeout=10,
            )
            resp.raise_for_status()
            data = resp.json()
            elevations = data.get("elevation", [])

            for j, elev in enumerate(elevations):
                idx = uncached_indices[start + j]
                lat, lng = uncached[start + j]
                key = (round(lat, 5), round(lng, 5))
                _cache[key] = elev
                results[idx] = elev
        except Exception:
            # Fallback: return 0 for failed lookups
            for j in range(len(batch)):
                idx = uncached_indices[start + j]
                if results[idx] is None:
                    results[idx] = 0.0

    return results


def get_elevation(lat, lng):
    """Get elevation for a single point."""
    result = get_elevations([(lat, lng)])
    return result[0] if result else 0.0


def get_path_elevations(start_lat, start_lng, end_lat, end_lng, num_samples=10):
    """Get elevations along a path between two points.

    Returns list of dicts with lat, lng, elevation, distance_m.
    """
    coords = []
    for i in range(num_samples):
        t = i / max(num_samples - 1, 1)
        lat = start_lat + t * (end_lat - start_lat)
        lng = start_lng + t * (end_lng - start_lng)
        coords.append((lat, lng))

    elevations = get_elevations(coords)

    results = []
    total_dist = 0
    for i, ((lat, lng), elev) in enumerate(zip(coords, elevations)):
        if i > 0:
            from math import radians, sin, cos, sqrt, atan2
            dlat = radians(lat - coords[i - 1][0])
            dlng = radians(lng - coords[i - 1][1])
            a = sin(dlat / 2) ** 2 + cos(radians(coords[i - 1][0])) * cos(radians(lat)) * sin(dlng / 2) ** 2
            total_dist += 6371000 * 2 * atan2(sqrt(a), sqrt(1 - a))

        results.append({
            "lat": lat,
            "lng": lng,
            "elevation": elev,
            "distance_m": round(total_dist, 1),
        })

    return results


def get_waypoint_elevations(waypoints):
    """Get elevations for a list of waypoint dicts. Returns enriched list."""
    if not waypoints:
        return []

    coords = [(w["lat"], w["lng"]) for w in waypoints]
    elevations = get_elevations(coords)

    result = []
    for wp, elev in zip(waypoints, elevations):
        enriched = dict(wp)
        enriched["ground_elevation_m"] = elev
        enriched["amsl_m"] = (elev or 0) + wp.get("altitude_m", 30)
        enriched["agl_m"] = wp.get("altitude_m", 30)
        result.append(enriched)

    return result


def clear_cache():
    """Clear the elevation cache."""
    _cache.clear()
