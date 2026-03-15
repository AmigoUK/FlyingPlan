import json
import os
import uuid
from flask import (
    render_template, redirect, url_for, flash, request, current_app
)
from werkzeug.utils import secure_filename
from extensions import db
from blueprints.public import public_bp
from models.flight_plan import FlightPlan
from models.poi import POI
from models.upload import Upload

ALLOWED_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "pdf", "doc", "docx"}


def _allowed_file(filename):
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXTENSIONS


@public_bp.route("/")
def form():
    return render_template("public/form.html")


@public_bp.route("/submit", methods=["POST"])
def submit():
    # Validate required fields
    errors = []
    customer_name = request.form.get("customer_name", "").strip()
    customer_email = request.form.get("customer_email", "").strip()
    job_type = request.form.get("job_type", "").strip()
    job_description = request.form.get("job_description", "").strip()
    location_lat = request.form.get("location_lat", "").strip()
    location_lng = request.form.get("location_lng", "").strip()
    consent = request.form.get("consent_given")

    if not customer_name:
        errors.append("Customer name is required.")
    if not customer_email:
        errors.append("Customer email is required.")
    if not job_type or job_type not in FlightPlan.JOB_TYPES:
        errors.append("Valid job type is required.")
    if not job_description:
        errors.append("Job description is required.")
    if not location_lat or not location_lng:
        errors.append("Location pin must be placed on the map.")
    if not consent:
        errors.append("You must give consent to proceed.")

    if errors:
        for e in errors:
            flash(e, "danger")
        return redirect(url_for("public.form"))

    # Create flight plan
    fp = FlightPlan(
        customer_name=customer_name,
        customer_email=customer_email,
        customer_phone=request.form.get("customer_phone", "").strip(),
        customer_company=request.form.get("customer_company", "").strip(),
        heard_about=request.form.get("heard_about", "").strip(),
        job_type=job_type,
        job_description=job_description,
        preferred_dates=request.form.get("preferred_dates", "").strip(),
        time_window=request.form.get("time_window", "").strip(),
        urgency=request.form.get("urgency", "normal").strip(),
        special_requirements=request.form.get("special_requirements", "").strip(),
        location_address=request.form.get("location_address", "").strip(),
        location_lat=float(location_lat),
        location_lng=float(location_lng),
        altitude_preset=request.form.get("altitude_preset", "medium").strip(),
        camera_angle=request.form.get("camera_angle", "pilot_decides").strip(),
        video_resolution=request.form.get("video_resolution", "4k").strip(),
        photo_mode=request.form.get("photo_mode", "single").strip(),
        no_fly_notes=request.form.get("no_fly_notes", "").strip(),
        privacy_notes=request.form.get("privacy_notes", "").strip(),
        consent_given=True,
    )

    # Altitude custom
    if fp.altitude_preset == "custom":
        try:
            fp.altitude_custom_m = float(request.form.get("altitude_custom_m", 30))
        except (ValueError, TypeError):
            fp.altitude_custom_m = 30.0

    # Area polygon
    area_polygon_raw = request.form.get("area_polygon", "").strip()
    if area_polygon_raw:
        try:
            polygon = json.loads(area_polygon_raw)
            fp.area_polygon = json.dumps(polygon)
            fp.estimated_area_sqm = _calc_polygon_area(polygon)
        except (json.JSONDecodeError, TypeError):
            pass

    # Generate reference
    fp.generate_reference()
    # Ensure uniqueness
    while FlightPlan.query.filter_by(reference=fp.reference).first():
        fp.generate_reference()

    db.session.add(fp)
    db.session.flush()  # get fp.id

    # POIs
    pois_json = request.form.get("pois_json", "").strip()
    if pois_json:
        try:
            pois = json.loads(pois_json)
            for i, p in enumerate(pois):
                poi = POI(
                    flight_plan_id=fp.id,
                    lat=float(p["lat"]),
                    lng=float(p["lng"]),
                    label=p.get("label", ""),
                    sort_order=i,
                )
                db.session.add(poi)
        except (json.JSONDecodeError, KeyError, TypeError):
            pass

    # File uploads
    files = request.files.getlist("attachments")
    for f in files:
        if f and f.filename and _allowed_file(f.filename):
            original = secure_filename(f.filename)
            stored = f"{uuid.uuid4().hex}_{original}"
            f.save(os.path.join(current_app.config["UPLOAD_FOLDER"], stored))
            upload = Upload(
                flight_plan_id=fp.id,
                original_filename=original,
                stored_filename=stored,
                file_size=f.content_length or 0,
                mime_type=f.content_type,
            )
            db.session.add(upload)

    db.session.commit()
    return redirect(url_for("public.confirmation", ref=fp.reference))


@public_bp.route("/confirmation")
def confirmation():
    ref = request.args.get("ref", "")
    return render_template("public/confirmation.html", reference=ref)


def _calc_polygon_area(coords):
    """Approximate area in square meters using the Shoelace formula on projected coords."""
    import math
    if len(coords) < 3:
        return 0.0

    # Convert to meters using equirectangular projection
    lat0 = coords[0][0]
    cos_lat = math.cos(math.radians(lat0))
    R = 6371000  # Earth radius in meters

    points = []
    for c in coords:
        x = math.radians(c[1]) * cos_lat * R
        y = math.radians(c[0]) * R
        points.append((x, y))

    # Shoelace
    n = len(points)
    area = 0.0
    for i in range(n):
        j = (i + 1) % n
        area += points[i][0] * points[j][1]
        area -= points[j][0] * points[i][1]
    return abs(area) / 2.0
