"""DJI drone profiles for KMZ generation.

Each profile contains the DJI enum values needed for template.kml / waylines.wpml
plus display metadata and flight limits.
"""

DRONE_PROFILES = {
    "mini_4_pro": {
        "display_name": "DJI Mini 4 Pro",
        "droneEnumValue": 68,
        "droneSubEnumValue": 0,
        "payloadEnumValue": 52,
        "max_altitude_m": 120,
        "max_speed_ms": 16,
        "max_wind_speed_ms": 10.7,
        "max_flight_time_min": 34,
        "sensor_width_mm": 9.7,
        "sensor_height_mm": 7.3,
        "focal_length_mm": 6.7,
        "image_width_px": 4032,
        "image_height_px": 3024,
    },
    "mini_5_pro": {
        "display_name": "DJI Mini 5 Pro",
        "droneEnumValue": 68,
        "droneSubEnumValue": 2,
        "payloadEnumValue": 52,
        "max_altitude_m": 120,
        "max_speed_ms": 16,
        "max_wind_speed_ms": 10.7,
        "max_flight_time_min": 37,
        "sensor_width_mm": 9.7,
        "sensor_height_mm": 7.3,
        "focal_length_mm": 6.7,
        "image_width_px": 4032,
        "image_height_px": 3024,
    },
    "mavic_3": {
        "display_name": "DJI Mavic 3",
        "droneEnumValue": 77,
        "droneSubEnumValue": 0,
        "payloadEnumValue": 65,
        "max_altitude_m": 120,
        "max_speed_ms": 21,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 46,
        "sensor_width_mm": 17.3,
        "sensor_height_mm": 13.0,
        "focal_length_mm": 12.3,
        "image_width_px": 5280,
        "image_height_px": 3956,
    },
    "mavic_3_pro": {
        "display_name": "DJI Mavic 3 Pro",
        "droneEnumValue": 77,
        "droneSubEnumValue": 2,
        "payloadEnumValue": 67,
        "max_altitude_m": 120,
        "max_speed_ms": 21,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 43,
        "sensor_width_mm": 17.3,
        "sensor_height_mm": 13.0,
        "focal_length_mm": 12.3,
        "image_width_px": 5280,
        "image_height_px": 3956,
    },
    "mavic_3_classic": {
        "display_name": "DJI Mavic 3 Classic",
        "droneEnumValue": 77,
        "droneSubEnumValue": 1,
        "payloadEnumValue": 66,
        "max_altitude_m": 120,
        "max_speed_ms": 21,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 46,
        "sensor_width_mm": 17.3,
        "sensor_height_mm": 13.0,
        "focal_length_mm": 12.3,
        "image_width_px": 5280,
        "image_height_px": 3956,
    },
    "mavic_4_pro": {
        "display_name": "DJI Mavic 4 Pro",
        "droneEnumValue": 78,
        "droneSubEnumValue": 0,
        "payloadEnumValue": 68,
        "max_altitude_m": 120,
        "max_speed_ms": 21,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 46,
        "sensor_width_mm": 17.3,
        "sensor_height_mm": 13.0,
        "focal_length_mm": 12.3,
        "image_width_px": 5280,
        "image_height_px": 3956,
    },
    "air_3": {
        "display_name": "DJI Air 3",
        "droneEnumValue": 89,
        "droneSubEnumValue": 0,
        "payloadEnumValue": 80,
        "max_altitude_m": 120,
        "max_speed_ms": 19,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 46,
        "sensor_width_mm": 9.7,
        "sensor_height_mm": 7.3,
        "focal_length_mm": 6.7,
        "image_width_px": 4032,
        "image_height_px": 3024,
    },
    "air_3s": {
        "display_name": "DJI Air 3S",
        "droneEnumValue": 89,
        "droneSubEnumValue": 1,
        "payloadEnumValue": 81,
        "max_altitude_m": 120,
        "max_speed_ms": 19,
        "max_wind_speed_ms": 12,
        "max_flight_time_min": 45,
        "sensor_width_mm": 13.2,
        "sensor_height_mm": 8.8,
        "focal_length_mm": 5.4,
        "image_width_px": 4032,
        "image_height_px": 3024,
    },
}

DEFAULT_DRONE = "mini_4_pro"


def get_profile(drone_model):
    """Return a drone profile dict. Falls back to Mini 4 Pro if unknown."""
    return DRONE_PROFILES.get(drone_model, DRONE_PROFILES[DEFAULT_DRONE])


def get_choices():
    """Return list of (value, display_name) for form dropdowns."""
    return [(k, v["display_name"]) for k, v in DRONE_PROFILES.items()]
