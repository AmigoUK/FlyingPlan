import re

from flask import flash, jsonify, redirect, render_template, request, url_for
from flask_login import login_required

from blueprints.settings import settings_bp
from extensions import db
from models.app_settings import AppSettings
from models.job_type import JobType
from models.purpose_option import PurposeOption
from models.heard_about_option import HeardAboutOption
from models.flight_plan import FlightPlan


def _is_ajax():
    return request.headers.get("X-Requested-With") == "XMLHttpRequest"


def _validate_color(color):
    return bool(re.match(r"^#[0-9a-fA-F]{6}$", color))


# ── Settings Page ────────────────────────────────────────────────

@settings_bp.route("/")
@login_required
def settings_page():
    settings = AppSettings.get()
    job_types = JobType.query.order_by(JobType.sort_order, JobType.id).all()
    purposes = PurposeOption.query.order_by(PurposeOption.sort_order, PurposeOption.id).all()
    heard_about = HeardAboutOption.query.order_by(HeardAboutOption.sort_order, HeardAboutOption.id).all()
    return render_template(
        "admin/settings.html",
        settings=settings,
        job_types=job_types,
        purposes=purposes,
        heard_about=heard_about,
    )


# ── Branding ─────────────────────────────────────────────────────

@settings_bp.route("/branding", methods=["POST"])
@login_required
def update_branding():
    settings = AppSettings.get()
    settings.business_name = request.form.get("business_name", "FlyingPlan").strip()
    settings.logo_url = request.form.get("logo_url", "").strip()
    settings.contact_email = request.form.get("contact_email", "").strip()
    settings.tagline = request.form.get("tagline", "").strip()

    color = request.form.get("primary_color", "#0d6efd").strip()
    if _validate_color(color):
        settings.primary_color = color

    db.session.commit()
    flash("Branding settings updated.", "success")
    return redirect(url_for("settings.settings_page"))


# ── Form Visibility ──────────────────────────────────────────────

@settings_bp.route("/form-visibility", methods=["POST"])
@login_required
def update_form_visibility():
    settings = AppSettings.get()
    settings.show_heard_about = "show_heard_about" in request.form
    settings.show_customer_type_toggle = "show_customer_type_toggle" in request.form
    settings.show_purpose_fields = "show_purpose_fields" in request.form
    settings.show_output_format = "show_output_format" in request.form
    db.session.commit()
    flash("Form visibility settings updated.", "success")
    return redirect(url_for("settings.settings_page"))


# ── Job Types ────────────────────────────────────────────────────

@settings_bp.route("/job-types/new", methods=["POST"])
@login_required
def create_job_type():
    value = request.form.get("value", "").strip()
    label = request.form.get("label", "").strip()
    if not value or not label:
        flash("Value and label are required.", "danger")
        return redirect(url_for("settings.settings_page"))

    if JobType.query.filter_by(value=value).first():
        flash(f"Job type '{value}' already exists.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", "bi-briefcase").strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    category = request.form.get("category", "technical").strip()
    if category not in ("technical", "creative", "other"):
        category = "technical"

    max_order = db.session.query(db.func.max(JobType.sort_order)).scalar() or 0
    jt = JobType(
        value=value, label=label, icon=icon,
        category=category, sort_order=max_order + 1,
    )
    db.session.add(jt)
    db.session.commit()
    flash(f"Job type '{jt.label}' created.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/job-types/<int:id>/edit", methods=["POST"])
@login_required
def edit_job_type(id):
    jt = db.get_or_404(JobType, id)
    label = request.form.get("label", "").strip()
    if not label:
        flash("Label is required.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", jt.icon).strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    category = request.form.get("category", jt.category).strip()
    if category not in ("technical", "creative", "other"):
        category = jt.category

    jt.label = label
    jt.icon = icon
    jt.category = category
    db.session.commit()
    flash(f"Job type '{jt.label}' updated.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/job-types/<int:id>/toggle", methods=["POST"])
@login_required
def toggle_job_type(id):
    jt = db.get_or_404(JobType, id)
    jt.is_active = not jt.is_active
    db.session.commit()

    state = "activated" if jt.is_active else "deactivated"
    if _is_ajax():
        return jsonify({"ok": True, "is_active": jt.is_active, "message": f"'{jt.label}' {state}."})

    flash(f"'{jt.label}' {state}.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/job-types/<int:id>/delete", methods=["POST"])
@login_required
def delete_job_type(id):
    jt = db.get_or_404(JobType, id)

    in_use = FlightPlan.query.filter_by(job_type=jt.value).first()
    if in_use:
        flash(f"Cannot delete '{jt.label}' — it is used by existing flight plans. Deactivate it instead.", "danger")
        return redirect(url_for("settings.settings_page"))

    label = jt.label
    db.session.delete(jt)
    db.session.commit()
    flash(f"Job type '{label}' deleted.", "success")
    return redirect(url_for("settings.settings_page"))


# ── Purpose Options ──────────────────────────────────────────────

@settings_bp.route("/purposes/new", methods=["POST"])
@login_required
def create_purpose():
    value = request.form.get("value", "").strip()
    label = request.form.get("label", "").strip()
    if not value or not label:
        flash("Value and label are required.", "danger")
        return redirect(url_for("settings.settings_page"))

    if PurposeOption.query.filter_by(value=value).first():
        flash(f"Purpose option '{value}' already exists.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", "bi-question-circle").strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    max_order = db.session.query(db.func.max(PurposeOption.sort_order)).scalar() or 0
    po = PurposeOption(
        value=value, label=label, icon=icon, sort_order=max_order + 1,
    )
    db.session.add(po)
    db.session.commit()
    flash(f"Purpose option '{po.label}' created.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/purposes/<int:id>/edit", methods=["POST"])
@login_required
def edit_purpose(id):
    po = db.get_or_404(PurposeOption, id)
    label = request.form.get("label", "").strip()
    if not label:
        flash("Label is required.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", po.icon).strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    po.label = label
    po.icon = icon
    db.session.commit()
    flash(f"Purpose option '{po.label}' updated.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/purposes/<int:id>/toggle", methods=["POST"])
@login_required
def toggle_purpose(id):
    po = db.get_or_404(PurposeOption, id)
    po.is_active = not po.is_active
    db.session.commit()

    state = "activated" if po.is_active else "deactivated"
    if _is_ajax():
        return jsonify({"ok": True, "is_active": po.is_active, "message": f"'{po.label}' {state}."})

    flash(f"'{po.label}' {state}.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/purposes/<int:id>/delete", methods=["POST"])
@login_required
def delete_purpose(id):
    po = db.get_or_404(PurposeOption, id)
    label = po.label
    db.session.delete(po)
    db.session.commit()
    flash(f"Purpose option '{label}' deleted.", "success")
    return redirect(url_for("settings.settings_page"))


# ── Heard About Options ──────────────────────────────────────────

@settings_bp.route("/heard-about/new", methods=["POST"])
@login_required
def create_heard_about():
    value = request.form.get("value", "").strip()
    label = request.form.get("label", "").strip()
    if not value or not label:
        flash("Value and label are required.", "danger")
        return redirect(url_for("settings.settings_page"))

    if HeardAboutOption.query.filter_by(value=value).first():
        flash(f"Heard-about option '{value}' already exists.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", "bi-question-circle").strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    max_order = db.session.query(db.func.max(HeardAboutOption.sort_order)).scalar() or 0
    ha = HeardAboutOption(
        value=value, label=label, icon=icon, sort_order=max_order + 1,
    )
    db.session.add(ha)
    db.session.commit()
    flash(f"Heard-about option '{ha.label}' created.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/heard-about/<int:id>/edit", methods=["POST"])
@login_required
def edit_heard_about(id):
    ha = db.get_or_404(HeardAboutOption, id)
    label = request.form.get("label", "").strip()
    if not label:
        flash("Label is required.", "danger")
        return redirect(url_for("settings.settings_page"))

    icon = request.form.get("icon", ha.icon).strip()
    if not icon.startswith("bi-"):
        icon = "bi-" + icon

    ha.label = label
    ha.icon = icon
    db.session.commit()
    flash(f"Heard-about option '{ha.label}' updated.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/heard-about/<int:id>/toggle", methods=["POST"])
@login_required
def toggle_heard_about(id):
    ha = db.get_or_404(HeardAboutOption, id)
    ha.is_active = not ha.is_active
    db.session.commit()

    state = "activated" if ha.is_active else "deactivated"
    if _is_ajax():
        return jsonify({"ok": True, "is_active": ha.is_active, "message": f"'{ha.label}' {state}."})

    flash(f"'{ha.label}' {state}.", "success")
    return redirect(url_for("settings.settings_page"))


@settings_bp.route("/heard-about/<int:id>/delete", methods=["POST"])
@login_required
def delete_heard_about(id):
    ha = db.get_or_404(HeardAboutOption, id)
    label = ha.label
    db.session.delete(ha)
    db.session.commit()
    flash(f"Heard-about option '{label}' deleted.", "success")
    return redirect(url_for("settings.settings_page"))
