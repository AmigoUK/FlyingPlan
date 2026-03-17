"""
Weather service using Open-Meteo API (free, no API key required).
Provides current conditions and 48h forecast for flight locations.
"""
import requests

FORECAST_API_URL = "https://api.open-meteo.com/v1/forecast"


def get_weather(lat, lng):
    """Get current weather + 48h forecast for a location.

    Returns dict with:
        current: {temp_c, wind_speed_kmh, wind_dir_deg, wind_gusts_kmh,
                  precipitation_mm, cloud_cover_pct, visibility_m}
        hourly: list of hourly forecasts (48h)
        warnings: list of warning strings
        error: error string if failed
    """
    try:
        resp = requests.get(
            FORECAST_API_URL,
            params={
                "latitude": f"{lat:.4f}",
                "longitude": f"{lng:.4f}",
                "current": "temperature_2m,wind_speed_10m,wind_direction_10m,"
                           "wind_gusts_10m,precipitation,cloud_cover,visibility",
                "hourly": "temperature_2m,wind_speed_10m,wind_direction_10m,"
                          "wind_gusts_10m,precipitation_probability,cloud_cover,visibility",
                "forecast_hours": 48,
                "wind_speed_unit": "kmh",
                "timezone": "auto",
            },
            timeout=10,
        )
        resp.raise_for_status()
        data = resp.json()
    except Exception as e:
        return {"current": None, "hourly": [], "warnings": [], "error": str(e)}

    current_data = data.get("current", {})
    current = {
        "temp_c": current_data.get("temperature_2m"),
        "wind_speed_kmh": current_data.get("wind_speed_10m"),
        "wind_dir_deg": current_data.get("wind_direction_10m"),
        "wind_gusts_kmh": current_data.get("wind_gusts_10m"),
        "precipitation_mm": current_data.get("precipitation"),
        "cloud_cover_pct": current_data.get("cloud_cover"),
        "visibility_m": current_data.get("visibility"),
    }

    # Parse hourly
    hourly_data = data.get("hourly", {})
    times = hourly_data.get("time", [])
    hourly = []
    for i, t in enumerate(times):
        hourly.append({
            "time": t,
            "temp_c": _safe_idx(hourly_data.get("temperature_2m"), i),
            "wind_speed_kmh": _safe_idx(hourly_data.get("wind_speed_10m"), i),
            "wind_dir_deg": _safe_idx(hourly_data.get("wind_direction_10m"), i),
            "wind_gusts_kmh": _safe_idx(hourly_data.get("wind_gusts_10m"), i),
            "precip_prob_pct": _safe_idx(hourly_data.get("precipitation_probability"), i),
            "cloud_cover_pct": _safe_idx(hourly_data.get("cloud_cover"), i),
            "visibility_m": _safe_idx(hourly_data.get("visibility"), i),
        })

    return {"current": current, "hourly": hourly, "warnings": [], "error": None}


def check_drone_warnings(weather_current, drone_profile=None):
    """Check weather against drone limits and return warnings.

    Args:
        weather_current: dict from get_weather()["current"]
        drone_profile: dict from drone_profiles.get_profile() (optional)

    Returns:
        list of warning strings
    """
    if not weather_current:
        return []

    warnings = []
    wind_kmh = weather_current.get("wind_speed_kmh") or 0
    gusts_kmh = weather_current.get("wind_gusts_kmh") or 0
    precip = weather_current.get("precipitation_mm") or 0
    visibility = weather_current.get("visibility_m") or 99999

    # Default limits (Mini 4 Pro class)
    max_wind_ms = 10.7
    if drone_profile:
        max_wind_ms = drone_profile.get("max_wind_speed_ms", 10.7)

    max_wind_kmh = max_wind_ms * 3.6

    if wind_kmh > max_wind_kmh:
        warnings.append(f"Wind speed ({wind_kmh:.0f} km/h) exceeds drone limit ({max_wind_kmh:.0f} km/h)")
    elif wind_kmh > max_wind_kmh * 0.8:
        warnings.append(f"Wind speed ({wind_kmh:.0f} km/h) near drone limit ({max_wind_kmh:.0f} km/h)")

    if gusts_kmh > max_wind_kmh * 1.2:
        warnings.append(f"Wind gusts ({gusts_kmh:.0f} km/h) dangerous for this drone")

    if precip > 0:
        warnings.append(f"Precipitation detected ({precip:.1f} mm) — most drones are not waterproof")

    if visibility < 1000:
        warnings.append(f"Low visibility ({visibility:.0f}m) — maintain VLOS")
    elif visibility < 3000:
        warnings.append(f"Reduced visibility ({visibility:.0f}m)")

    return warnings


def _safe_idx(lst, idx):
    if lst and idx < len(lst):
        return lst[idx]
    return None
