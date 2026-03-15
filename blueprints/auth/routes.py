from datetime import datetime, timezone
from flask import render_template, redirect, url_for, flash, request
from flask_login import login_user, logout_user, login_required, current_user
from blueprints.auth import auth_bp
from models.user import User

# Simple rate limiting
_failed_attempts = {}
MAX_FAILURES = 5
LOCKOUT_SECONDS = 30


@auth_bp.route("/login", methods=["GET", "POST"])
def login():
    if current_user.is_authenticated:
        if current_user.role == "pilot":
            return redirect(url_for("pilot.dashboard"))
        return redirect(url_for("admin.dashboard"))

    if request.method == "POST":
        ip = request.remote_addr
        now = datetime.now(timezone.utc).timestamp()

        # Check rate limit
        attempts = _failed_attempts.get(ip, [])
        attempts = [t for t in attempts if now - t < LOCKOUT_SECONDS]
        _failed_attempts[ip] = attempts

        if len(attempts) >= MAX_FAILURES:
            flash("Too many failed attempts. Please wait.", "danger")
            return render_template("admin/login.html"), 429

        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")
        remember = request.form.get("remember") == "on"

        user = User.query.filter_by(username=username).first()
        if user and user.is_active and user.check_password(password):
            login_user(user, remember=remember)
            _failed_attempts.pop(ip, None)
            next_page = request.args.get("next")
            if next_page:
                return redirect(next_page)
            if user.role == "pilot":
                return redirect(url_for("pilot.dashboard"))
            return redirect(url_for("admin.dashboard"))

        attempts.append(now)
        _failed_attempts[ip] = attempts
        flash("Invalid username or password.", "danger")

    return render_template("admin/login.html")


@auth_bp.route("/logout", methods=["POST"])
@login_required
def logout():
    logout_user()
    flash("You have been logged out.", "info")
    return redirect(url_for("auth.login"))
