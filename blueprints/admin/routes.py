import json
from flask import (
    render_template, redirect, url_for, flash, request, jsonify, send_file
)
from extensions import db
from blueprints.admin import admin_bp
from blueprints.auth.decorators import role_required
from models.flight_plan import FlightPlan
from models.user import User
from models.waypoint import Waypoint


@admin_bp.route("/")
@role_required("manager")
def dashboard():
    status_filter = request.args.get("status", "")
    job_type_filter = request.args.get("job_type", "")
    search = request.args.get("q", "").strip()

    query = FlightPlan.query

    if status_filter:
        query = query.filter(FlightPlan.status == status_filter)
    if job_type_filter:
        query = query.filter(FlightPlan.job_type == job_type_filter)
    if search:
        query = query.filter(
            db.or_(
                FlightPlan.customer_name.ilike(f"%{search}%"),
                FlightPlan.reference.ilike(f"%{search}%"),
                FlightPlan.customer_email.ilike(f"%{search}%"),
                FlightPlan.customer_company.ilike(f"%{search}%"),
            )
        )

    plans = query.order_by(FlightPlan.created_at.desc()).all()
    available_pilots = User.query.filter_by(
        role="pilot", is_active_user=True
    ).order_by(User.display_name).all()
    return render_template(
        "admin/dashboard.html",
        plans=plans,
        status_filter=status_filter,
        job_type_filter=job_type_filter,
        search=search,
        available_pilots=available_pilots,
    )


@admin_bp.route("/<int:plan_id>")
@role_required("manager")
def detail(plan_id):
    from services.drone_profiles import get_choices
    fp = db.get_or_404(FlightPlan, plan_id)
    waypoints_json = json.dumps([w.to_dict() for w in fp.waypoints])
    pois_json = json.dumps(
        [{"lat": p.lat, "lng": p.lng, "label": p.label} for p in fp.pois]
    )
    available_pilots = User.query.filter_by(
        role="pilot", is_active_user=True
    ).order_by(User.display_name).all()
    return render_template(
        "admin/detail.html",
        fp=fp,
        waypoints_json=waypoints_json,
        pois_json=pois_json,
        available_pilots=available_pilots,
        drone_choices=get_choices(),
    )


@admin_bp.route("/<int:plan_id>/waypoints", methods=["POST"])
@role_required("manager")
def save_waypoints(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    if not data or not isinstance(data, list):
        return jsonify({"error": "Invalid data"}), 400

    # Replace all existing waypoints
    Waypoint.query.filter_by(flight_plan_id=fp.id).delete()
    for i, w in enumerate(data):
        wp = Waypoint(
            flight_plan_id=fp.id,
            index=i,
            lat=float(w["lat"]),
            lng=float(w["lng"]),
            altitude_m=float(w.get("altitude_m", 30.0)),
            speed_ms=float(w.get("speed_ms", 5.0)),
            heading_deg=w.get("heading_deg"),
            gimbal_pitch_deg=float(w.get("gimbal_pitch_deg", -90.0)),
            turn_mode=w.get("turn_mode", "toPointAndStopWithDiscontinuityCurvature"),
            turn_damping_dist=float(w.get("turn_damping_dist", 0.0)),
            hover_time_s=float(w.get("hover_time_s", 0.0)),
            action_type=w.get("action_type") or None,
            poi_lat=w.get("poi_lat"),
            poi_lng=w.get("poi_lng"),
        )
        db.session.add(wp)

    if fp.status == "new":
        fp.status = "in_review"
    db.session.commit()
    return jsonify({"success": True, "count": len(data)})


@admin_bp.route("/<int:plan_id>/status", methods=["POST"])
@role_required("manager")
def update_status(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    new_status = data.get("status", "")
    if new_status in FlightPlan.STATUSES:
        fp.status = new_status
        db.session.commit()
        return jsonify({"success": True, "status": new_status})
    return jsonify({"error": "Invalid status"}), 400


@admin_bp.route("/<int:plan_id>/notes", methods=["POST"])
@role_required("manager")
def save_notes(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    fp.admin_notes = data.get("notes", "")
    db.session.commit()
    return jsonify({"success": True})


@admin_bp.route("/<int:plan_id>/gsd", methods=["POST"])
@role_required("manager")
def calculate_gsd(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json() or {}
    from services.gsd_calculator import calculate_gsd as calc_gsd
    result = calc_gsd(
        drone_model=fp.drone_model or "mini_4_pro",
        altitude_m=float(data.get("altitude_m", 30)),
        overlap_pct=float(data.get("overlap_pct", 70)),
        area_sqm=fp.estimated_area_sqm,
    )
    return jsonify(result)


@admin_bp.route("/<int:plan_id>/generate-pattern", methods=["POST"])
@role_required("manager")
def generate_pattern(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    pattern_type = data.get("type", "orbit")
    config = data.get("config", {})

    from services.mission_patterns import generate_orbit, generate_spiral, generate_cable_cam

    if pattern_type == "orbit":
        wps = generate_orbit(
            config.get("center_lat", fp.location_lat),
            config.get("center_lng", fp.location_lng),
            radius_m=config.get("radius_m", 30),
            altitude_m=config.get("altitude_m", 30),
            num_points=int(config.get("num_points", 12)),
            speed_ms=config.get("speed_ms", 5),
        )
    elif pattern_type == "spiral":
        wps = generate_spiral(
            config.get("center_lat", fp.location_lat),
            config.get("center_lng", fp.location_lng),
            radius_m=config.get("radius_m", 30),
            start_altitude_m=config.get("start_altitude_m", 20),
            end_altitude_m=config.get("end_altitude_m", 60),
            num_revolutions=int(config.get("num_revolutions", 3)),
            speed_ms=config.get("speed_ms", 4),
        )
    elif pattern_type == "cable_cam":
        wps = generate_cable_cam(
            config.get("start_lat", fp.location_lat),
            config.get("start_lng", fp.location_lng),
            config.get("end_lat", fp.location_lat + 0.001),
            config.get("end_lng", fp.location_lng + 0.001),
            altitude_m=config.get("altitude_m", 30),
            num_points=int(config.get("num_points", 10)),
            speed_ms=config.get("speed_ms", 3),
        )
    else:
        return jsonify({"error": "Unknown pattern type"}), 400

    if not wps:
        return jsonify({"error": "Failed to generate pattern"}), 400

    Waypoint.query.filter_by(flight_plan_id=fp.id).delete()
    for w in wps:
        wp = Waypoint(
            flight_plan_id=fp.id, index=w["index"],
            lat=w["lat"], lng=w["lng"],
            altitude_m=w.get("altitude_m", 30),
            speed_ms=w.get("speed_ms", 5),
            heading_deg=w.get("heading_deg"),
            gimbal_pitch_deg=w.get("gimbal_pitch_deg", -90),
            turn_mode=w.get("turn_mode", "toPointAndStopWithDiscontinuityCurvature"),
            action_type=w.get("action_type"),
            poi_lat=w.get("poi_lat"),
            poi_lng=w.get("poi_lng"),
        )
        db.session.add(wp)

    if fp.status == "new":
        fp.status = "in_review"
    db.session.commit()
    return jsonify({"success": True, "count": len(wps), "waypoints": wps})


@admin_bp.route("/<int:plan_id>/airspace")
@role_required("manager")
def get_airspace(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    from services.airspace import get_airspace_geojson, check_route_airspace
    geojson = get_airspace_geojson()
    violations = {}
    if fp.waypoints:
        violations = check_route_airspace([w.to_dict() for w in fp.waypoints], geojson)
    return jsonify({"geojson": geojson, "violations": violations})


@admin_bp.route("/<int:plan_id>/weather")
@role_required("manager")
def get_weather(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    if not fp.location_lat or not fp.location_lng:
        return jsonify({"error": "No location set"}), 400

    from services.weather import get_weather as fetch_weather, check_drone_warnings
    from services.drone_profiles import get_profile
    weather = fetch_weather(fp.location_lat, fp.location_lng)
    profile = get_profile(fp.drone_model or "mini_4_pro")
    weather["warnings"] = check_drone_warnings(weather.get("current"), profile)
    return jsonify(weather)


@admin_bp.route("/<int:plan_id>/terrain-follow", methods=["POST"])
@role_required("manager")
def terrain_follow(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    if not fp.waypoints:
        return jsonify({"error": "No waypoints to adjust"}), 400

    data = request.get_json() or {}
    target_agl = float(data.get("target_agl_m", 30))

    from services.terrain_follower import apply_terrain_following
    waypoints_data = [w.to_dict() for w in fp.waypoints]
    adjusted = apply_terrain_following(waypoints_data, target_agl_m=target_agl)

    # Replace waypoints
    Waypoint.query.filter_by(flight_plan_id=fp.id).delete()
    for w in adjusted:
        wp = Waypoint(
            flight_plan_id=fp.id,
            index=w["index"],
            lat=w["lat"],
            lng=w["lng"],
            altitude_m=w.get("altitude_m", 30.0),
            speed_ms=w.get("speed_ms", 5.0),
            heading_deg=w.get("heading_deg"),
            gimbal_pitch_deg=w.get("gimbal_pitch_deg", -90.0),
            turn_mode=w.get("turn_mode", "toPointAndStopWithDiscontinuityCurvature"),
            action_type=w.get("action_type"),
        )
        db.session.add(wp)

    db.session.commit()
    return jsonify({"success": True, "count": len(adjusted), "waypoints": adjusted})


@admin_bp.route("/<int:plan_id>/generate-grid", methods=["POST"])
@role_required("manager")
def generate_grid(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    polygon_str = data.get("polygon") or fp.area_polygon
    if not polygon_str:
        return jsonify({"error": "No polygon area defined"}), 400

    import json as json_mod
    if isinstance(polygon_str, str):
        polygon_coords = json_mod.loads(polygon_str)
    else:
        polygon_coords = polygon_str

    config = data.get("config", {})
    from services.grid_generator import generate_grid as gen_grid
    waypoints = gen_grid(polygon_coords, config)

    if not waypoints:
        return jsonify({"error": "Could not generate grid — polygon too small or invalid"}), 400

    # Replace existing waypoints
    Waypoint.query.filter_by(flight_plan_id=fp.id).delete()
    for w in waypoints:
        wp = Waypoint(
            flight_plan_id=fp.id,
            index=w["index"],
            lat=w["lat"],
            lng=w["lng"],
            altitude_m=w.get("altitude_m", 30.0),
            speed_ms=w.get("speed_ms", 5.0),
            heading_deg=w.get("heading_deg"),
            gimbal_pitch_deg=w.get("gimbal_pitch_deg", -90.0),
            turn_mode=w.get("turn_mode", "toPointAndStopWithDiscontinuityCurvature"),
            action_type=w.get("action_type"),
        )
        db.session.add(wp)

    if fp.status == "new":
        fp.status = "in_review"
    db.session.commit()
    return jsonify({"success": True, "count": len(waypoints), "waypoints": waypoints})


@admin_bp.route("/<int:plan_id>/elevation", methods=["POST"])
@role_required("manager")
def get_elevation(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    from services.elevation import get_waypoint_elevations
    waypoints_data = [w.to_dict() for w in fp.waypoints]
    enriched = get_waypoint_elevations(waypoints_data)
    return jsonify({"success": True, "waypoints": enriched})


@admin_bp.route("/<int:plan_id>/import-kmz", methods=["POST"])
@role_required("manager")
def import_kmz(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    file = request.files.get("kmz_file")
    if not file or not file.filename:
        return jsonify({"error": "No file uploaded"}), 400

    from services.kmz_parser import parse_kmz
    result = parse_kmz(file.read())
    if result["error"]:
        return jsonify({"error": result["error"]}), 400

    if not result["waypoints"]:
        return jsonify({"error": "No waypoints found in KMZ"}), 400

    # Replace existing waypoints
    Waypoint.query.filter_by(flight_plan_id=fp.id).delete()
    for w in result["waypoints"]:
        wp = Waypoint(
            flight_plan_id=fp.id,
            index=w["index"],
            lat=w["lat"],
            lng=w["lng"],
            altitude_m=w.get("altitude_m", 30.0),
            speed_ms=w.get("speed_ms", 5.0),
            heading_deg=w.get("heading_deg"),
            gimbal_pitch_deg=w.get("gimbal_pitch_deg", -90.0),
            turn_mode=w.get("turn_mode", "toPointAndStopWithDiscontinuityCurvature"),
            turn_damping_dist=w.get("turn_damping_dist", 0.0),
            hover_time_s=w.get("hover_time_s", 0.0),
            action_type=w.get("action_type"),
        )
        db.session.add(wp)

    # Update drone model if detected
    if result["drone_model"]:
        fp.drone_model = result["drone_model"]

    if fp.status == "new":
        fp.status = "in_review"
    db.session.commit()

    return jsonify({
        "success": True,
        "count": len(result["waypoints"]),
        "drone_model": result["drone_model"],
        "waypoints": result["waypoints"],
    })


@admin_bp.route("/<int:plan_id>/drone-model", methods=["POST"])
@role_required("manager")
def save_drone_model(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    data = request.get_json()
    from services.drone_profiles import DRONE_PROFILES
    model = data.get("drone_model", "mini_4_pro")
    if model in DRONE_PROFILES:
        fp.drone_model = model
        db.session.commit()
        return jsonify({"success": True, "drone_model": model})
    return jsonify({"error": "Invalid drone model"}), 400


@admin_bp.route("/<int:plan_id>/duplicate", methods=["POST"])
@role_required("manager")
def duplicate_plan(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    new_fp = FlightPlan(
        customer_name=fp.customer_name,
        customer_email=fp.customer_email,
        customer_phone=fp.customer_phone,
        customer_company=fp.customer_company,
        job_type=fp.job_type,
        job_description=fp.job_description,
        location_address=fp.location_address,
        location_lat=fp.location_lat,
        location_lng=fp.location_lng,
        area_polygon=fp.area_polygon,
        estimated_area_sqm=fp.estimated_area_sqm,
        altitude_preset=fp.altitude_preset,
        altitude_custom_m=fp.altitude_custom_m,
        drone_model=fp.drone_model,
        consent_given=True,
    )
    new_fp.generate_reference()
    db.session.add(new_fp)
    db.session.flush()

    # Copy waypoints
    for w in fp.waypoints:
        new_wp = Waypoint(
            flight_plan_id=new_fp.id,
            index=w.index, lat=w.lat, lng=w.lng,
            altitude_m=w.altitude_m, speed_ms=w.speed_ms,
            heading_deg=w.heading_deg, gimbal_pitch_deg=w.gimbal_pitch_deg,
            turn_mode=w.turn_mode, turn_damping_dist=w.turn_damping_dist,
            hover_time_s=w.hover_time_s, action_type=w.action_type,
            poi_lat=w.poi_lat, poi_lng=w.poi_lng,
        )
        db.session.add(new_wp)

    db.session.commit()
    return jsonify({"success": True, "new_plan_id": new_fp.id, "reference": new_fp.reference})


@admin_bp.route("/<int:plan_id>/export-kmz")
@role_required("manager")
def export_kmz(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    if not fp.waypoints:
        flash("No waypoints to export.", "warning")
        return redirect(url_for("admin.detail", plan_id=plan_id))

    from services.kmz_generator import generate_kmz
    kmz_buffer = generate_kmz(fp)

    return send_file(
        kmz_buffer,
        mimetype="application/vnd.google-earth.kmz",
        as_attachment=True,
        download_name=f"{fp.reference}.kmz",
    )
