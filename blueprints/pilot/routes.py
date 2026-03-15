import os
import uuid
from datetime import datetime, timezone

from flask import (
    abort, current_app, flash, redirect, render_template, request,
    send_file, url_for,
)
from flask_login import current_user
from werkzeug.utils import secure_filename

from blueprints.auth.decorators import role_required
from blueprints.pilot import pilot_bp
from extensions import db
from models.order import Order
from models.order_activity import OrderActivity
from models.order_deliverable import OrderDeliverable
from models.pilot_certification import PilotCertification
from models.pilot_equipment import PilotEquipment
from models.pilot_document import PilotDocument
from models.user import User


def _log_activity(order, action, old_value=None, new_value=None, details=None):
    activity = OrderActivity(
        order_id=order.id,
        user_id=current_user.id,
        action=action,
        old_value=old_value,
        new_value=new_value,
        details=details,
    )
    db.session.add(activity)


def _get_pilot_order(order_id):
    """Get order and verify pilot ownership."""
    order = db.get_or_404(Order, order_id)
    if order.pilot_id != current_user.id:
        abort(403)
    return order


def _deliverable_dir(order_id):
    path = os.path.join(
        current_app.config["UPLOAD_FOLDER"], "orders", str(order_id)
    )
    os.makedirs(path, exist_ok=True)
    return path


def _pilot_upload_dir(user_id):
    path = os.path.join(
        current_app.config["UPLOAD_FOLDER"], "pilots", str(user_id)
    )
    os.makedirs(path, exist_ok=True)
    return path


ALLOWED_DELIVERABLE_EXT = {
    "png", "jpg", "jpeg", "gif", "mp4", "mov", "avi",
    "pdf", "zip", "tiff", "tif",
}
ALLOWED_DOC_EXT = {"png", "jpg", "jpeg", "gif", "pdf", "doc", "docx"}

PILOT_FORWARD_STATUSES = {
    "assigned": ["accepted", "declined"],
    "accepted": ["in_progress"],
    "in_progress": ["flight_complete"],
    "flight_complete": ["delivered"],
}


# ── Dashboard ───────────────────────────────────────────────────

@pilot_bp.route("/")
@role_required("pilot")
def dashboard():
    orders = Order.query.filter_by(pilot_id=current_user.id).order_by(
        Order.created_at.desc()
    ).all()
    return render_template("pilot/dashboard.html", orders=orders)


# ── Profile ─────────────────────────────────────────────────────

@pilot_bp.route("/profile", methods=["GET", "POST"])
@role_required("pilot")
def profile():
    if request.method == "POST":
        current_user.display_name = request.form.get("display_name", current_user.display_name).strip()
        current_user.email = request.form.get("email", "").strip() or None
        current_user.phone = request.form.get("phone", "").strip() or None
        current_user.flying_id = request.form.get("flying_id", "").strip() or None
        current_user.operator_id = request.form.get("operator_id", "").strip() or None
        current_user.insurance_provider = request.form.get("insurance_provider", "").strip() or None
        current_user.insurance_policy_no = request.form.get("insurance_policy_no", "").strip() or None
        current_user.pilot_bio = request.form.get("pilot_bio", "").strip() or None
        exp = request.form.get("insurance_expiry", "").strip()
        if exp:
            try:
                current_user.insurance_expiry = datetime.strptime(exp, "%Y-%m-%d").date()
            except ValueError:
                pass
        else:
            current_user.insurance_expiry = None
        new_password = request.form.get("password", "").strip()
        if new_password:
            current_user.set_password(new_password)
        db.session.commit()
        flash("Profile updated.", "success")
        return redirect(url_for("pilot.profile"))

    return render_template("pilot/profile.html")


# ── Order Detail ────────────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>")
@role_required("pilot")
def order_detail(order_id):
    order = _get_pilot_order(order_id)
    allowed_next = PILOT_FORWARD_STATUSES.get(order.status, [])
    return render_template(
        "pilot/order_detail.html", order=order, allowed_next=allowed_next
    )


# ── Accept Order ────────────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>/accept", methods=["POST"])
@role_required("pilot")
def accept_order(order_id):
    order = _get_pilot_order(order_id)
    if order.status != "assigned":
        flash("Cannot accept this order.", "warning")
        return redirect(url_for("pilot.order_detail", order_id=order.id))
    order.status = "accepted"
    order.accepted_at = datetime.now(timezone.utc)
    _log_activity(order, "accepted")
    db.session.commit()
    flash("Order accepted.", "success")
    return redirect(url_for("pilot.order_detail", order_id=order.id))


# ── Decline Order ───────────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>/decline", methods=["POST"])
@role_required("pilot")
def decline_order(order_id):
    order = _get_pilot_order(order_id)
    if order.status != "assigned":
        flash("Cannot decline this order.", "warning")
        return redirect(url_for("pilot.order_detail", order_id=order.id))
    order.status = "declined"
    order.decline_reason = request.form.get("reason", "").strip() or None
    _log_activity(order, "declined", details=order.decline_reason)
    db.session.commit()
    flash("Order declined.", "info")
    return redirect(url_for("pilot.dashboard"))


# ── Advance Status ──────────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>/status", methods=["POST"])
@role_required("pilot")
def update_status(order_id):
    order = _get_pilot_order(order_id)
    new_status = request.form.get("status", "")
    allowed = PILOT_FORWARD_STATUSES.get(order.status, [])
    if new_status not in allowed:
        flash("Invalid status transition.", "danger")
        return redirect(url_for("pilot.order_detail", order_id=order.id))

    old_status = order.status
    order.status = new_status
    now = datetime.now(timezone.utc)
    if new_status == "in_progress":
        order.started_at = order.started_at or now
    elif new_status == "flight_complete":
        order.completed_at = order.completed_at or now
    elif new_status == "delivered":
        order.delivered_at = order.delivered_at or now

    _log_activity(order, "status_changed", old_value=old_status, new_value=new_status)
    db.session.commit()
    flash(f"Status updated to {new_status.replace('_', ' ').title()}.", "success")
    return redirect(url_for("pilot.order_detail", order_id=order.id))


# ── Pilot Notes ─────────────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>/notes", methods=["POST"])
@role_required("pilot")
def save_notes(order_id):
    order = _get_pilot_order(order_id)
    order.pilot_notes = request.form.get("pilot_notes", "").strip()
    _log_activity(order, "note_added", details="Pilot notes updated")
    db.session.commit()
    flash("Notes saved.", "success")
    return redirect(url_for("pilot.order_detail", order_id=order.id))


# ── Upload Deliverable ──────────────────────────────────────────

@pilot_bp.route("/orders/<int:order_id>/deliverables", methods=["POST"])
@role_required("pilot")
def upload_deliverable(order_id):
    order = _get_pilot_order(order_id)
    file = request.files.get("file")
    if not file or not file.filename:
        flash("No file selected.", "danger")
        return redirect(url_for("pilot.order_detail", order_id=order.id))

    ext = file.filename.rsplit(".", 1)[-1].lower() if "." in file.filename else ""
    if ext not in ALLOWED_DELIVERABLE_EXT:
        flash("File type not allowed.", "danger")
        return redirect(url_for("pilot.order_detail", order_id=order.id))

    original = secure_filename(file.filename)
    stored = f"{uuid.uuid4().hex}_{original}"
    upload_dir = _deliverable_dir(order.id)
    file.save(os.path.join(upload_dir, stored))

    deliv = OrderDeliverable(
        order_id=order.id,
        uploaded_by_id=current_user.id,
        original_filename=original,
        stored_filename=stored,
        file_size=os.path.getsize(os.path.join(upload_dir, stored)),
        mime_type=file.content_type,
        description=request.form.get("description", "").strip() or None,
    )
    db.session.add(deliv)
    _log_activity(order, "deliverable_uploaded", new_value=original)
    db.session.commit()
    flash("Deliverable uploaded.", "success")
    return redirect(url_for("pilot.order_detail", order_id=order.id))


@pilot_bp.route("/orders/<int:order_id>/deliverables/<int:d_id>/delete", methods=["POST"])
@role_required("pilot")
def delete_deliverable(order_id, d_id):
    order = _get_pilot_order(order_id)
    deliv = db.get_or_404(OrderDeliverable, d_id)
    if deliv.order_id != order.id:
        abort(403)
    filepath = os.path.join(_deliverable_dir(order.id), deliv.stored_filename)
    if os.path.exists(filepath):
        os.remove(filepath)
    db.session.delete(deliv)
    db.session.commit()
    flash("Deliverable removed.", "success")
    return redirect(url_for("pilot.order_detail", order_id=order.id))


# ── Certifications ──────────────────────────────────────────────

@pilot_bp.route("/certifications/add", methods=["POST"])
@role_required("pilot")
def add_certification():
    cert = PilotCertification(
        user_id=current_user.id,
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
        return redirect(url_for("pilot.profile"))
    db.session.add(cert)
    db.session.commit()
    flash("Certification added.", "success")
    return redirect(url_for("pilot.profile"))


@pilot_bp.route("/certifications/<int:cert_id>/delete", methods=["POST"])
@role_required("pilot")
def delete_certification(cert_id):
    cert = db.get_or_404(PilotCertification, cert_id)
    if cert.user_id != current_user.id:
        abort(403)
    db.session.delete(cert)
    db.session.commit()
    flash("Certification deleted.", "success")
    return redirect(url_for("pilot.profile"))


# ── Equipment ───────────────────────────────────────────────────

@pilot_bp.route("/equipment/add", methods=["POST"])
@role_required("pilot")
def add_equipment():
    equip = PilotEquipment(
        user_id=current_user.id,
        drone_model=request.form.get("drone_model", "").strip(),
        serial_number=request.form.get("serial_number", "").strip() or None,
        registration_id=request.form.get("registration_id", "").strip() or None,
        notes=request.form.get("notes", "").strip() or None,
    )
    if not equip.drone_model:
        flash("Drone model is required.", "danger")
        return redirect(url_for("pilot.profile"))
    db.session.add(equip)
    db.session.commit()
    flash("Equipment added.", "success")
    return redirect(url_for("pilot.profile"))


@pilot_bp.route("/equipment/<int:equip_id>/delete", methods=["POST"])
@role_required("pilot")
def delete_equipment(equip_id):
    equip = db.get_or_404(PilotEquipment, equip_id)
    if equip.user_id != current_user.id:
        abort(403)
    db.session.delete(equip)
    db.session.commit()
    flash("Equipment removed.", "success")
    return redirect(url_for("pilot.profile"))


# ── Documents ───────────────────────────────────────────────────

@pilot_bp.route("/documents/upload", methods=["POST"])
@role_required("pilot")
def upload_document():
    file = request.files.get("file")
    if not file or not file.filename:
        flash("No file selected.", "danger")
        return redirect(url_for("pilot.profile"))
    ext = file.filename.rsplit(".", 1)[-1].lower() if "." in file.filename else ""
    if ext not in ALLOWED_DOC_EXT:
        flash("File type not allowed.", "danger")
        return redirect(url_for("pilot.profile"))

    original = secure_filename(file.filename)
    stored = f"{uuid.uuid4().hex}_{original}"
    upload_dir = _pilot_upload_dir(current_user.id)
    file.save(os.path.join(upload_dir, stored))

    doc = PilotDocument(
        user_id=current_user.id,
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
    return redirect(url_for("pilot.profile"))


@pilot_bp.route("/documents/<int:doc_id>/delete", methods=["POST"])
@role_required("pilot")
def delete_document(doc_id):
    doc = db.get_or_404(PilotDocument, doc_id)
    if doc.user_id != current_user.id:
        abort(403)
    filepath = os.path.join(_pilot_upload_dir(current_user.id), doc.stored_filename)
    if os.path.exists(filepath):
        os.remove(filepath)
    db.session.delete(doc)
    db.session.commit()
    flash("Document deleted.", "success")
    return redirect(url_for("pilot.profile"))


@pilot_bp.route("/documents/<int:doc_id>/download")
@role_required("pilot")
def download_document(doc_id):
    doc = db.get_or_404(PilotDocument, doc_id)
    if doc.user_id != current_user.id:
        abort(403)
    return send_file(
        os.path.join(_pilot_upload_dir(current_user.id), doc.stored_filename),
        as_attachment=True,
        download_name=doc.original_filename,
    )
