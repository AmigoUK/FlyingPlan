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
