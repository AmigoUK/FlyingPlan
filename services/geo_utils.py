"""
Shared coordinate utility functions for geo calculations.
Extracted from grid_generator.py and mission_patterns.py for reuse
across oblique grid, facade scanning, and coverage analysis.
"""
import math


def to_metres(lat, lng, center_lat, center_lng):
    """Convert lat/lng to local metres (equirectangular projection)."""
    x = (lng - center_lng) * math.cos(math.radians(center_lat)) * 111320
    y = (lat - center_lat) * 110540
    return x, y


def to_latlng(x, y, center_lat, center_lng):
    """Convert local metres back to lat/lng."""
    lat = center_lat + y / 110540
    lng = center_lng + x / (math.cos(math.radians(center_lat)) * 111320)
    return lat, lng


def rotate(x, y, angle_rad):
    """Rotate point around origin."""
    cos_a = math.cos(angle_rad)
    sin_a = math.sin(angle_rad)
    return x * cos_a - y * sin_a, x * sin_a + y * cos_a


def offset_point(lat, lng, distance_m, bearing_rad):
    """Calculate a point at distance/bearing from origin (equirectangular)."""
    dx = distance_m * math.sin(bearing_rad)
    dy = distance_m * math.cos(bearing_rad)
    new_lat = lat + dy / 110540
    new_lng = lng + dx / (111320 * math.cos(math.radians(lat)))
    return round(new_lat, 7), round(new_lng, 7)


def heading_to(from_lat, from_lng, to_lat, to_lng):
    """Calculate heading in degrees from one point to another."""
    dlng = math.radians(to_lng - from_lng)
    lat1 = math.radians(from_lat)
    lat2 = math.radians(to_lat)
    x = math.sin(dlng) * math.cos(lat2)
    y = (math.cos(lat1) * math.sin(lat2) -
         math.sin(lat1) * math.cos(lat2) * math.cos(dlng))
    heading = math.degrees(math.atan2(x, y))
    return round(heading % 360, 1)


def haversine(lat1, lng1, lat2, lng2):
    """Calculate distance in metres between two points using Haversine."""
    R = 6371000
    dlat = math.radians(lat2 - lat1)
    dlng = math.radians(lng2 - lng1)
    a = (math.sin(dlat / 2) ** 2 +
         math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
         math.sin(dlng / 2) ** 2)
    return R * 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def polygon_centroid(coords):
    """Calculate centroid of a polygon given as list of [lat, lng]."""
    n = len(coords)
    if n == 0:
        return 0, 0
    lat = sum(c[0] for c in coords) / n
    lng = sum(c[1] for c in coords) / n
    return lat, lng
