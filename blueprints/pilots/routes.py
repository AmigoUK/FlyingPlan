import os
import uuid
from datetime import datetime

from flask import (
    current_app, flash, jsonify, redirect, render_template, request,
    send_file, url_for,
)
from flask_login import current_user
from werkzeug.utils import secure_filename

from blueprints.auth.decorators import role_required
from blueprints.pilots import pilots_bp
from extensions import db
from models.user import User
from models.pilot_certification import PilotCertification
from models.pilot_equipment import PilotEquipment
from models.pilot_document import PilotDocument

ALLOWED_DOC_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "pdf", "doc", "docx"}


def _allowed_doc(filename):
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_DOC_EXTENSIONS


def _pilot_upload_dir(user_id):
    path = os.path.join(current_app.config["UPLOAD_FOLDER"], "pilots", str(user_id))
    os.makedirs(path, exist_ok=True)
    return path


# ── List Pilots ─────────────────────────────────────────────────

@pilots_bp.route("/")
@role_required("manager")
def list_pilots():
    pilots = User.query.filter_by(role="pilot").order_by(User.display_name).all()
    return render_template("admin/pilots/list.html", pilots=pilots)


# ── Create Pilot ────────────────────────────────────────────────

@pilots_bp.route("/new", methods=["GET", "POST"])
@role_required("admin")
def create_pilot():
    if request.method == "POST":
        username = request.form.get("username", "").strip()
        display_name = request.form.get("display_name", "").strip()
        password = request.form.get("password", "").strip()

        if not username or not display_name or not password:
            flash("Username, display name, and password are required.", "danger")
            return render_template("admin/pilots/form.html", pilot=None)

        if User.query.filter_by(username=username).first():
            flash(f"Username '{username}' already exists.", "danger")
            return render_template("admin/pilots/form.html", pilot=None)

        pilot = User(
            username=username,
            display_name=display_name,
            role="pilot",
            email=request.form.get("email", "").strip() or None,
            phone=request.form.get("phone", "").strip() or None,
            flying_id=request.form.get("flying_id", "").strip() or None,
            operator_id=request.form.get("operator_id", "").strip() or None,
            insurance_provider=request.form.get("insurance_provider", "").strip() or None,
            insurance_policy_no=request.form.get("insurance_policy_no", "").strip() or None,
            pilot_bio=request.form.get("pilot_bio", "").strip() or None,
        )
        exp = request.form.get("insurance_expiry", "").strip()
        if exp:
            try:
                pilot.insurance_expiry = datetime.strptime(exp, "%Y-%m-%d").date()
            except ValueError:
                pass
        pilot.set_password(password)
        db.session.add(pilot)
        db.session.commit()
        flash(f"Pilot '{display_name}' created.", "success")
        return redirect(url_for("pilots.detail", pilot_id=pilot.id))

    return render_template("admin/pilots/form.html", pilot=None)


# ── Pilot Detail ────────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>")
@role_required("manager")
def detail(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    if pilot.role != "pilot":
        flash("User is not a pilot.", "warning")
        return redirect(url_for("pilots.list_pilots"))
    return render_template("admin/pilots/detail.html", pilot=pilot)


# ── Edit Pilot ──────────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/edit", methods=["POST"])
@role_required("admin")
def edit_pilot(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    pilot.display_name = request.form.get("display_name", pilot.display_name).strip()
    pilot.email = request.form.get("email", "").strip() or None
    pilot.phone = request.form.get("phone", "").strip() or None
    pilot.flying_id = request.form.get("flying_id", "").strip() or None
    pilot.operator_id = request.form.get("operator_id", "").strip() or None
    pilot.insurance_provider = request.form.get("insurance_provider", "").strip() or None
    pilot.insurance_policy_no = request.form.get("insurance_policy_no", "").strip() or None
    pilot.pilot_bio = request.form.get("pilot_bio", "").strip() or None
    exp = request.form.get("insurance_expiry", "").strip()
    if exp:
        try:
            pilot.insurance_expiry = datetime.strptime(exp, "%Y-%m-%d").date()
        except ValueError:
            pass
    else:
        pilot.insurance_expiry = None

    new_password = request.form.get("password", "").strip()
    if new_password:
        pilot.set_password(new_password)

    db.session.commit()
    flash("Pilot profile updated.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot.id))


# ── Toggle Active ───────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/toggle-active", methods=["POST"])
@role_required("admin")
def toggle_active(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    pilot.is_active_user = not pilot.is_active_user
    db.session.commit()
    state = "activated" if pilot.is_active_user else "deactivated"
    if request.headers.get("X-Requested-With") == "XMLHttpRequest":
        return jsonify({"ok": True, "is_active": pilot.is_active_user, "message": f"Pilot {state}."})
    flash(f"Pilot {state}.", "success")
    return redirect(url_for("pilots.list_pilots"))


# ── Availability ────────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/availability", methods=["POST"])
@role_required("manager")
def set_availability(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    status = request.form.get("status", "available")
    if status in User.AVAILABILITY_STATUSES:
        pilot.availability_status = status
        db.session.commit()
    if request.headers.get("X-Requested-With") == "XMLHttpRequest":
        return jsonify({"ok": True, "status": pilot.availability_status})
    flash("Availability updated.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot.id))


# ── Certifications ──────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/certifications/add", methods=["POST"])
@role_required("admin")
def add_certification(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    cert = PilotCertification(
        user_id=pilot.id,
        cert_name=request.form.get("cert_name", "").strip(),
        issuing_body=request.form.get("issuing_body", "").strip() or None,
        cert_number=request.form.get("cert_number", "").strip() or None,
    )
    issue = request.form.get("issue_date", "").strip()
    if issue:
        try:
            cert.issue_date = datetime.strptime(issue, "%Y-%m-%d").date()
        except ValueError:
            pass
    expiry = request.form.get("expiry_date", "").strip()
    if expiry:
        try:
            cert.expiry_date = datetime.strptime(expiry, "%Y-%m-%d").date()
        except ValueError:
            pass
    if not cert.cert_name:
        flash("Certification name is required.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot.id))
    db.session.add(cert)
    db.session.commit()
    flash("Certification added.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot.id))


@pilots_bp.route("/<int:pilot_id>/certifications/<int:cert_id>/delete", methods=["POST"])
@role_required("admin")
def delete_certification(pilot_id, cert_id):
    cert = db.get_or_404(PilotCertification, cert_id)
    if cert.user_id != pilot_id:
        flash("Invalid certification.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot_id))
    db.session.delete(cert)
    db.session.commit()
    flash("Certification deleted.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot_id))


# ── Equipment ───────────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/equipment/add", methods=["POST"])
@role_required("admin")
def add_equipment(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    equip = PilotEquipment(
        user_id=pilot.id,
        drone_model=request.form.get("drone_model", "").strip(),
        serial_number=request.form.get("serial_number", "").strip() or None,
        registration_id=request.form.get("registration_id", "").strip() or None,
        notes=request.form.get("notes", "").strip() or None,
    )
    if not equip.drone_model:
        flash("Drone model is required.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot.id))
    db.session.add(equip)
    db.session.commit()
    flash("Equipment added.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot.id))


@pilots_bp.route("/<int:pilot_id>/equipment/<int:equip_id>/delete", methods=["POST"])
@role_required("admin")
def delete_equipment(pilot_id, equip_id):
    equip = db.get_or_404(PilotEquipment, equip_id)
    if equip.user_id != pilot_id:
        flash("Invalid equipment.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot_id))
    db.session.delete(equip)
    db.session.commit()
    flash("Equipment removed.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot_id))


# ── Documents ───────────────────────────────────────────────────

@pilots_bp.route("/<int:pilot_id>/documents/upload", methods=["POST"])
@role_required("admin")
def upload_document(pilot_id):
    pilot = db.get_or_404(User, pilot_id)
    file = request.files.get("file")
    if not file or not file.filename:
        flash("No file selected.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot.id))
    if not _allowed_doc(file.filename):
        flash("File type not allowed.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot.id))

    original = secure_filename(file.filename)
    stored = f"{uuid.uuid4().hex}_{original}"
    upload_dir = _pilot_upload_dir(pilot.id)
    file.save(os.path.join(upload_dir, stored))

    doc = PilotDocument(
        user_id=pilot.id,
        doc_type=request.form.get("doc_type", "other"),
        label=request.form.get("label", original).strip() or original,
        original_filename=original,
        stored_filename=stored,
        file_size=os.path.getsize(os.path.join(upload_dir, stored)),
        mime_type=file.content_type,
    )
    expiry = request.form.get("expiry_date", "").strip()
    if expiry:
        try:
            doc.expiry_date = datetime.strptime(expiry, "%Y-%m-%d").date()
        except ValueError:
            pass
    db.session.add(doc)
    db.session.commit()
    flash("Document uploaded.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot.id))


@pilots_bp.route("/<int:pilot_id>/documents/<int:doc_id>/download")
@role_required("manager")
def download_document(pilot_id, doc_id):
    doc = db.get_or_404(PilotDocument, doc_id)
    if doc.user_id != pilot_id:
        flash("Invalid document.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot_id))
    upload_dir = _pilot_upload_dir(pilot_id)
    return send_file(
        os.path.join(upload_dir, doc.stored_filename),
        as_attachment=True,
        download_name=doc.original_filename,
    )


@pilots_bp.route("/<int:pilot_id>/documents/<int:doc_id>/delete", methods=["POST"])
@role_required("admin")
def delete_document(pilot_id, doc_id):
    doc = db.get_or_404(PilotDocument, doc_id)
    if doc.user_id != pilot_id:
        flash("Invalid document.", "danger")
        return redirect(url_for("pilots.detail", pilot_id=pilot_id))
    filepath = os.path.join(_pilot_upload_dir(pilot_id), doc.stored_filename)
    if os.path.exists(filepath):
        os.remove(filepath)
    db.session.delete(doc)
    db.session.commit()
    flash("Document deleted.", "success")
    return redirect(url_for("pilots.detail", pilot_id=pilot_id))
