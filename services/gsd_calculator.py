"""
Ground Sampling Distance (GSD) calculator for survey/mapping missions.

Given camera specs (from drone profiles), altitude, and overlap, calculates:
- GSD (cm/pixel)
- Ground footprint per image
- Optimal line spacing
- Photo count estimate
- Estimated flight time
- Battery usage
"""
from services.drone_profiles import get_profile


def calculate_gsd(drone_model, altitude_m, overlap_pct=70, area_sqm=None):
    """Calculate GSD and related survey metrics.

    Args:
        drone_model: key from DRONE_PROFILES
        altitude_m: flight altitude in metres AGL
        overlap_pct: front/side overlap percentage (0-99)
        area_sqm: optional area in sq metres for photo/time estimates

    Returns:
        dict with all calculated metrics
    """
    profile = get_profile(drone_model)

    sensor_w = profile["sensor_width_mm"]
    sensor_h = profile["sensor_height_mm"]
    focal = profile["focal_length_mm"]
    img_w = profile["image_width_px"]
    img_h = profile["image_height_px"]
    max_flight_min = profile.get("max_flight_time_min", 30)

    # GSD = (sensor_size * altitude * 100) / (focal_length * image_size)
    gsd_w_cm = (sensor_w * altitude_m * 100) / (focal * img_w)
    gsd_h_cm = (sensor_h * altitude_m * 100) / (focal * img_h)
    gsd_cm = max(gsd_w_cm, gsd_h_cm)

    # Ground footprint per image
    footprint_w_m = (sensor_w * altitude_m) / focal
    footprint_h_m = (sensor_h * altitude_m) / focal

    # Effective footprint after overlap
    overlap_factor = 1 - (overlap_pct / 100)
    effective_w_m = footprint_w_m * overlap_factor
    effective_h_m = footprint_h_m * overlap_factor

    # Line spacing (side overlap)
    line_spacing_m = effective_w_m

    # Photo interval along line (front overlap)
    photo_interval_m = effective_h_m

    result = {
        "gsd_cm_per_px": round(gsd_cm, 2),
        "footprint_width_m": round(footprint_w_m, 1),
        "footprint_height_m": round(footprint_h_m, 1),
        "line_spacing_m": round(line_spacing_m, 1),
        "photo_interval_m": round(photo_interval_m, 1),
        "overlap_pct": overlap_pct,
        "altitude_m": altitude_m,
        "drone_model": drone_model,
        "drone_name": profile["display_name"],
        "sensor_info": f"{sensor_w}x{sensor_h}mm, {focal}mm, {img_w}x{img_h}px",
    }

    # Quality tier
    if gsd_cm <= 1:
        result["quality_tier"] = "Ultra High (< 1 cm/px)"
    elif gsd_cm <= 2:
        result["quality_tier"] = "High (1-2 cm/px)"
    elif gsd_cm <= 5:
        result["quality_tier"] = "Standard (2-5 cm/px)"
    else:
        result["quality_tier"] = "Low (> 5 cm/px)"

    # Area-based estimates
    if area_sqm and area_sqm > 0:
        num_lines = max(1, area_sqm ** 0.5 / line_spacing_m)
        photos_per_line = max(1, area_sqm ** 0.5 / photo_interval_m)
        total_photos = int(num_lines * photos_per_line)
        total_distance_m = num_lines * area_sqm ** 0.5

        # Estimate flight time (5 m/s default speed + 2s per photo)
        flight_time_s = total_distance_m / 5 + total_photos * 2
        flight_time_min = flight_time_s / 60
        battery_pct = min(100, (flight_time_min / max_flight_min) * 100)

        result["estimated_photos"] = total_photos
        result["estimated_flight_time_min"] = round(flight_time_min, 1)
        result["estimated_battery_pct"] = round(battery_pct, 0)
        result["batteries_needed"] = max(1, int(battery_pct / 80) + (1 if battery_pct % 80 > 0 else 0))

    return result


def recommend_altitude(drone_model, target_gsd_cm):
    """Calculate the altitude needed to achieve a target GSD.

    Returns altitude in metres.
    """
    profile = get_profile(drone_model)
    sensor_w = profile["sensor_width_mm"]
    focal = profile["focal_length_mm"]
    img_w = profile["image_width_px"]

    # GSD = (sensor_w * alt * 100) / (focal * img_w)
    # alt = (GSD * focal * img_w) / (sensor_w * 100)
    altitude = (target_gsd_cm * focal * img_w) / (sensor_w * 100)
    return round(altitude, 1)
