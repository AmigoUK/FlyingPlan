import json
import os
import uuid
from datetime import datetime, timezone

from flask import (
    abort, current_app, flash, jsonify, redirect, render_template, request,
    send_file, url_for,
)
from flask_login import current_user
from werkzeug.utils import secure_filename

from blueprints.auth.decorators import role_required
from blueprints.orders import orders_bp
from extensions import db
from models.flight_plan import FlightPlan
from models.order import Order
from models.order_activity import OrderActivity
from models.order_deliverable import OrderDeliverable
from models.user import User


def _log_activity(order, action, old_value=None, new_value=None, details=None):
    activity = OrderActivity(
        order_id=order.id,
        user_id=current_user.id if current_user.is_authenticated else None,
        action=action,
        old_value=old_value,
        new_value=new_value,
        details=details,
    )
    db.session.add(activity)


def _deliverable_dir(order_id):
    path = os.path.join(
        current_app.config["UPLOAD_FOLDER"], "orders", str(order_id)
    )
    os.makedirs(path, exist_ok=True)
    return path


# ── List Orders ─────────────────────────────────────────────────

@orders_bp.route("/")
@role_required("manager")
def list_orders():
    status_filter = request.args.get("status", "")
    pilot_filter = request.args.get("pilot_id", "", type=str)

    query = Order.query
    if status_filter:
        query = query.filter(Order.status == status_filter)
    if pilot_filter:
        query = query.filter(Order.pilot_id == int(pilot_filter))

    orders = query.order_by(Order.created_at.desc()).all()
    pilots = User.query.filter_by(role="pilot", is_active_user=True).order_by(User.display_name).all()
    return render_template(
        "admin/orders/list.html",
        orders=orders,
        pilots=pilots,
        status_filter=status_filter,
        pilot_filter=pilot_filter,
    )


# ── Create Order ────────────────────────────────────────────────

@orders_bp.route("/create/<int:plan_id>", methods=["POST"])
@role_required("manager")
def create_order(plan_id):
    fp = db.get_or_404(FlightPlan, plan_id)
    if fp.order:
        flash("An order already exists for this flight plan.", "warning")
        return redirect(url_for("orders.detail", order_id=fp.order.id))

    pilot_id = request.form.get("pilot_id", type=int)
    order = Order(
        flight_plan_id=fp.id,
        assigned_by_id=current_user.id,
    )

    sched = request.form.get("scheduled_date", "").strip()
    if sched:
        try:
            order.scheduled_date = datetime.strptime(sched, "%Y-%m-%d").date()
        except ValueError:
            pass
    order.scheduled_time = request.form.get("scheduled_time", "").strip() or None
    order.assignment_notes = request.form.get("assignment_notes", "").strip() or None

    if pilot_id:
        order.pilot_id = pilot_id
        order.status = "assigned"
        order.assigned_at = datetime.now(timezone.utc)

    db.session.add(order)
    db.session.flush()

    _log_activity(order, "created")
    if pilot_id:
        pilot = db.session.get(User, pilot_id)
        _log_activity(
            order, "assigned",
            new_value=pilot.display_name if pilot else str(pilot_id),
        )

    db.session.commit()
    flash("Order created.", "success")
    return redirect(url_for("orders.detail", order_id=order.id))


# ── Order Detail ────────────────────────────────────────────────

@orders_bp.route("/<int:order_id>")
@role_required("manager")
def detail(order_id):
    order = db.get_or_404(Order, order_id)
    pilots = User.query.filter_by(role="pilot", is_active_user=True).order_by(
        User.display_name
    ).all()
    fp = order.flight_plan
    pois_json = json.dumps(
        [{"lat": p.lat, "lng": p.lng, "label": p.label} for p in fp.pois]
    )
    waypoints_json = json.dumps([w.to_dict() for w in fp.waypoints])
    return render_template(
        "admin/orders/detail.html",
        order=order,
        pilots=pilots,
        pois_json=pois_json,
        waypoints_json=waypoints_json,
    )


# ── Assign / Reassign Pilot ────────────────────────────────────

@orders_bp.route("/<int:order_id>/assign", methods=["POST"])
@role_required("manager")
def assign_pilot(order_id):
    order = db.get_or_404(Order, order_id)
    pilot_id = request.form.get("pilot_id", type=int)
    if not pilot_id:
        flash("Please select a pilot.", "danger")
        return redirect(url_for("orders.detail", order_id=order.id))

    pilot = db.get_or_404(User, pilot_id)
    old_pilot = order.pilot.display_name if order.pilot else None

    order.pilot_id = pilot.id
    order.assigned_by_id = current_user.id
    order.assigned_at = datetime.now(timezone.utc)
    order.status = "assigned"
    order.decline_reason = None

    sched = request.form.get("scheduled_date", "").strip()
    if sched:
        try:
            order.scheduled_date = datetime.strptime(sched, "%Y-%m-%d").date()
        except ValueError:
            pass
    order.scheduled_time = request.form.get("scheduled_time", "").strip() or order.scheduled_time
    notes = request.form.get("assignment_notes", "").strip()
    if notes:
        order.assignment_notes = notes

    _log_activity(
        order, "assigned",
        old_value=old_pilot,
        new_value=pilot.display_name,
    )
    db.session.commit()
    flash(f"Order assigned to {pilot.display_name}.", "success")
    return redirect(url_for("orders.detail", order_id=order.id))


# ── Admin Status Override ───────────────────────────────────────

ADMIN_VALID_TRANSITIONS = {
    "pending_assignment": ["assigned", "cancelled", "closed"],
    "assigned": ["accepted", "declined", "pending_assignment", "closed"],
    "accepted": ["in_progress", "declined", "assigned", "closed"],
    "in_progress": ["flight_complete", "closed"],
    "flight_complete": ["delivered", "closed"],
    "delivered": ["closed"],
    "declined": ["assigned", "pending_assignment", "closed"],
    "closed": [],
}


@orders_bp.route("/<int:order_id>/status", methods=["POST"])
@role_required("manager")
def update_status(order_id):
    order = db.get_or_404(Order, order_id)
    new_status = request.form.get("status", "")
    if new_status not in Order.STATUSES:
        flash("Invalid status.", "danger")
        return redirect(url_for("orders.detail", order_id=order.id))

    allowed = ADMIN_VALID_TRANSITIONS.get(order.status, [])
    if new_status not in allowed:
        flash(
            f"Cannot change status from {order.status.replace('_', ' ').title()} "
            f"to {new_status.replace('_', ' ').title()}.",
            "danger",
        )
        return redirect(url_for("orders.detail", order_id=order.id))

    old_status = order.status
    order.status = new_status
    now = datetime.now(timezone.utc)

    if new_status == "accepted":
        order.accepted_at = order.accepted_at or now
    elif new_status == "in_progress":
        order.started_at = order.started_at or now
    elif new_status == "flight_complete":
        order.completed_at = order.completed_at or now
    elif new_status == "delivered":
        order.delivered_at = order.delivered_at or now
    elif new_status == "closed":
        order.closed_at = order.closed_at or now

    _log_activity(order, "status_changed", old_value=old_status, new_value=new_status)
    db.session.commit()
    flash(f"Status changed to {new_status.replace('_', ' ').title()}.", "success")
    return redirect(url_for("orders.detail", order_id=order.id))


# ── Admin Notes ─────────────────────────────────────────────────

@orders_bp.route("/<int:order_id>/notes", methods=["POST"])
@role_required("manager")
def save_notes(order_id):
    order = db.get_or_404(Order, order_id)
    order.assignment_notes = request.form.get("assignment_notes", "").strip()
    _log_activity(order, "note_added", details="Admin notes updated")
    db.session.commit()
    flash("Notes saved.", "success")
    return redirect(url_for("orders.detail", order_id=order.id))


# ── Download Deliverable ───────────────────────────────────────

@orders_bp.route("/<int:order_id>/deliverables/<int:d_id>/download")
@role_required("manager")
def download_deliverable(order_id, d_id):
    deliv = db.get_or_404(OrderDeliverable, d_id)
    if deliv.order_id != order_id:
        flash("Invalid deliverable.", "danger")
        return redirect(url_for("orders.detail", order_id=order_id))
    base_dir = _deliverable_dir(order_id)
    filepath = os.path.realpath(os.path.join(base_dir, deliv.stored_filename))
    if not filepath.startswith(os.path.realpath(base_dir)):
        abort(403)
    return send_file(
        filepath,
        as_attachment=True,
        download_name=deliv.original_filename,
    )


# ── PDF Report ─────────────────────────────────────────────────

@orders_bp.route("/<int:order_id>/report-pdf")
@role_required("manager")
def report_pdf(order_id):
    order = db.get_or_404(Order, order_id)
    from services.pdf_report import generate_report_pdf
    buf = generate_report_pdf(order, include_admin_notes=True)
    return send_file(
        buf,
        mimetype="application/pdf",
        as_attachment=True,
        download_name=f"{order.flight_plan.reference}.pdf",
    )
