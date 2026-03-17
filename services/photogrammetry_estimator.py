"""
Photogrammetry quality estimator.

Predicts 3D reconstruction quality based on:
- Image overlap (from coverage analysis)
- Convergence angles between overlapping views
- GSD (ground sampling distance)
- Point density estimation
"""
import math
from services.geo_utils import heading_to, haversine
from services.drone_profiles import get_profile
from services.coverage_analyzer import compute_coverage_grid


def estimate_point_density(coverage_data, drone_model="mini_4_pro"):
    """Estimate 3D point cloud density from coverage data.

    Rule of thumb: ~1000 points/m² at 1cm GSD with 80% overlap.
    Scales with GSD² and overlap quality.

    Args:
        coverage_data: output from compute_coverage_grid
        drone_model: drone profile key

    Returns:
        dict with estimated_points_per_sqm, quality_label
    """
    stats = coverage_data.get("stats", {})
    avg_overlap = stats.get("avg_overlap", 0)

    if avg_overlap <= 0:
        return {"estimated_points_per_sqm": 0, "quality_label": "none"}

    # Base density scales with overlap count
    # 3 images = ~100 pts/m², 5 = ~300, 8+ = ~800
    base_density = min(1000, avg_overlap * avg_overlap * 15)

    if base_density >= 500:
        label = "excellent"
    elif base_density >= 200:
        label = "good"
    elif base_density >= 50:
        label = "moderate"
    else:
        label = "poor"

    return {
        "estimated_points_per_sqm": round(base_density, 0),
        "quality_label": label,
    }


def compute_convergence_angles(waypoints, drone_model="mini_4_pro",
                                sample_points=None):
    """Compute convergence angles between overlapping views at sample points.

    Good convergence (20-90 deg) is essential for accurate depth estimation.
    Nadir-only missions have poor convergence (~0 deg between parallel passes).

    Args:
        waypoints: list of waypoint dicts
        drone_model: drone profile key
        sample_points: optional list of [lat, lng] ground sample points.
                       If None, uses waypoint positions as samples.

    Returns:
        dict with:
            avg_angle: average convergence angle in degrees
            min_angle: minimum convergence angle
            max_angle: maximum convergence angle
            quality_label: 'excellent', 'good', 'moderate', 'poor'
            sample_results: list of { lat, lng, angle }
    """
    if not waypoints or len(waypoints) < 2:
        return {"avg_angle": 0, "min_angle": 0, "max_angle": 0,
                "quality_label": "poor", "sample_results": []}

    if sample_points is None:
        # Use waypoint midpoints as sample points
        sample_points = []
        for i in range(0, len(waypoints), max(1, len(waypoints) // 10)):
            sample_points.append([waypoints[i]["lat"], waypoints[i]["lng"]])

    results = []
    for sp in sample_points:
        sp_lat, sp_lng = sp[0], sp[1]

        # Find all waypoints within reasonable distance that could see this point
        viewing_wps = []
        for wp in waypoints:
            dist = haversine(sp_lat, sp_lng, wp["lat"], wp["lng"])
            if dist < 500:  # Within 500m
                bearing = heading_to(wp["lat"], wp["lng"], sp_lat, sp_lng)
                viewing_wps.append({
                    "bearing": bearing,
                    "distance": dist,
                    "altitude": wp.get("altitude_m", 30),
                })

        if len(viewing_wps) < 2:
            results.append({"lat": sp_lat, "lng": sp_lng, "angle": 0})
            continue

        # Compute max convergence angle between any pair
        max_angle = 0
        for i in range(len(viewing_wps)):
            for j in range(i + 1, len(viewing_wps)):
                diff = abs(viewing_wps[i]["bearing"] - viewing_wps[j]["bearing"])
                if diff > 180:
                    diff = 360 - diff
                max_angle = max(max_angle, diff)

        results.append({"lat": sp_lat, "lng": sp_lng, "angle": round(max_angle, 1)})

    angles = [r["angle"] for r in results]
    avg_angle = sum(angles) / len(angles) if angles else 0

    if avg_angle >= 45:
        label = "excellent"
    elif avg_angle >= 25:
        label = "good"
    elif avg_angle >= 10:
        label = "moderate"
    else:
        label = "poor"

    return {
        "avg_angle": round(avg_angle, 1),
        "min_angle": min(angles) if angles else 0,
        "max_angle": max(angles) if angles else 0,
        "quality_label": label,
        "sample_results": results,
    }


def generate_quality_report(waypoints, drone_model="mini_4_pro",
                             polygon=None):
    """Generate a comprehensive photogrammetry quality report.

    Returns traffic-light ratings (red/yellow/green) for each metric
    with recommended adjustments.

    Args:
        waypoints: list of waypoint dicts
        drone_model: drone profile key
        polygon: optional polygon coords for coverage area

    Returns:
        dict with metrics, ratings, and recommendations
    """
    if not waypoints:
        return {
            "overall_rating": "red",
            "metrics": {},
            "recommendations": ["Add waypoints to generate a quality report."],
        }

    # Coverage analysis
    coverage = compute_coverage_grid(waypoints, drone_model)
    stats = coverage.get("stats", {})

    # Point density estimation
    density = estimate_point_density(coverage, drone_model)

    # Convergence angles
    convergence = compute_convergence_angles(waypoints, drone_model)

    # GSD estimation (simple)
    profile = get_profile(drone_model)
    altitudes = [w.get("altitude_m", 30) for w in waypoints]
    avg_alt = sum(altitudes) / len(altitudes)
    gsd_cm = (avg_alt * profile["sensor_width_mm"]) / (
        profile["focal_length_mm"] * profile["image_width_px"]
    ) * 100

    # Detect capture mode
    pitches = [w.get("gimbal_pitch_deg", -90) for w in waypoints]
    has_nadir = any(p <= -80 for p in pitches)
    has_oblique = any(-75 < p < -10 for p in pitches)

    if has_nadir and has_oblique:
        capture_mode = "double_grid"
    elif has_oblique:
        capture_mode = "oblique_only"
    else:
        capture_mode = "nadir_only"

    # Rate each metric
    metrics = {}

    # Overlap rating
    avg_overlap = stats.get("avg_overlap", 0)
    if avg_overlap >= 5:
        metrics["overlap"] = {"value": avg_overlap, "rating": "green",
                              "label": "Image Overlap"}
    elif avg_overlap >= 3:
        metrics["overlap"] = {"value": avg_overlap, "rating": "yellow",
                              "label": "Image Overlap"}
    else:
        metrics["overlap"] = {"value": avg_overlap, "rating": "red",
                              "label": "Image Overlap"}

    # Coverage rating
    suff_pct = stats.get("sufficient_pct", 0)
    if suff_pct >= 90:
        metrics["coverage"] = {"value": suff_pct, "rating": "green",
                               "label": "Coverage (% with 3+ images)"}
    elif suff_pct >= 70:
        metrics["coverage"] = {"value": suff_pct, "rating": "yellow",
                               "label": "Coverage (% with 3+ images)"}
    else:
        metrics["coverage"] = {"value": suff_pct, "rating": "red",
                               "label": "Coverage (% with 3+ images)"}

    # GSD rating
    if gsd_cm <= 2:
        metrics["gsd"] = {"value": round(gsd_cm, 2), "rating": "green",
                          "label": "GSD (cm/px)"}
    elif gsd_cm <= 5:
        metrics["gsd"] = {"value": round(gsd_cm, 2), "rating": "yellow",
                          "label": "GSD (cm/px)"}
    else:
        metrics["gsd"] = {"value": round(gsd_cm, 2), "rating": "red",
                          "label": "GSD (cm/px)"}

    # Convergence rating
    metrics["convergence"] = {
        "value": convergence["avg_angle"],
        "rating": convergence["quality_label"].replace("excellent", "green")
                  .replace("good", "green").replace("moderate", "yellow")
                  .replace("poor", "red"),
        "label": "Convergence Angle (deg)",
    }

    # Point density rating
    metrics["point_density"] = {
        "value": density["estimated_points_per_sqm"],
        "rating": density["quality_label"].replace("excellent", "green")
                  .replace("good", "green").replace("moderate", "yellow")
                  .replace("poor", "red").replace("none", "red"),
        "label": "Est. Point Density (pts/m²)",
    }

    # Capture mode rating
    mode_ratings = {
        "nadir_only": "red",
        "oblique_only": "yellow",
        "double_grid": "green",
    }
    metrics["capture_mode"] = {
        "value": capture_mode.replace("_", " ").title(),
        "rating": mode_ratings.get(capture_mode, "yellow"),
        "label": "Capture Mode",
    }

    # Overall rating
    ratings = [m["rating"] for m in metrics.values()]
    if "red" in ratings:
        overall = "red"
    elif "yellow" in ratings:
        overall = "yellow"
    else:
        overall = "green"

    # Recommendations
    recs = []
    if capture_mode == "nadir_only":
        recs.append("Add oblique passes (double-grid or multi-angle) for 3D reconstruction.")
    if avg_overlap < 3:
        recs.append("Increase overlap by reducing grid spacing or lowering altitude.")
    if suff_pct < 70:
        recs.append("Expand coverage area or add more flight lines to reduce gaps.")
    if convergence["avg_angle"] < 20:
        recs.append("Add perpendicular flight passes to improve convergence angles.")
    if gsd_cm > 5:
        recs.append("Lower flight altitude to improve ground resolution (GSD).")

    return {
        "overall_rating": overall,
        "metrics": metrics,
        "recommendations": recs,
        "capture_mode": capture_mode,
        "coverage_stats": stats,
        "convergence": convergence,
        "point_density": density,
    }
