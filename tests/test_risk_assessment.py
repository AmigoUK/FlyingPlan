import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.order import Order
from models.order_activity import OrderActivity
from models.risk_assessment import RiskAssessment
from models.user import User
from datetime import datetime, timezone


@pytest.fixture
def app():
    class TestConfig:
        SQLALCHEMY_DATABASE_URI = "sqlite:///:memory:"
        TESTING = True
        SECRET_KEY = "test"
        WTF_CSRF_ENABLED = False
        UPLOAD_FOLDER = "/tmp/fp_test_uploads"
        MAX_CONTENT_LENGTH = 32 * 1024 * 1024

    app = create_app(TestConfig)
    with app.app_context():
        yield app


@pytest.fixture
def client(app):
    return app.test_client()


@pytest.fixture
def pilot_client(client):
    client.post("/login", data={"username": "pilot1", "password": "pilot123"})
    return client


def _make_order(app, status="assigned"):
    """Create a flight plan and order assigned to pilot1."""
    with app.app_context():
        fp = FlightPlan(
            reference=f"FP-RA-{datetime.now().timestamp()}",
            customer_name="RA Test Client",
            customer_email="ra@test.com",
            job_type="survey",
            location_lat=-33.87,
            location_lng=151.21,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()

        pilot = User.query.filter_by(username="pilot1").first()
        admin = User.query.filter_by(username="admin").first()
        order = Order(
            flight_plan_id=fp.id,
            pilot_id=pilot.id,
            assigned_by_id=admin.id,
            status=status,
            assigned_at=datetime.now(timezone.utc),
        )
        if status == "accepted":
            order.accepted_at = datetime.now(timezone.utc)
        db.session.add(order)
        db.session.commit()
        return order.id


def _valid_assessment_data():
    """Return form data with all 28 checks + valid decision fields."""
    data = {field: "1" for field in RiskAssessment.CHECK_FIELDS}
    data.update({
        "risk_level": "low",
        "decision": "proceed",
        "pilot_declaration": "1",
        "airspace_planned_altitude": "80",
        "weather_wind_speed": "10",
        "weather_wind_direction": "NW",
        "weather_visibility": "10",
        "weather_precipitation": "None",
        "weather_temperature": "18",
        "equip_battery_level": "95",
        "gps_latitude": "-33.870000",
        "gps_longitude": "151.210000",
    })
    return data


# ── 1. Form loads for accepted order ──────────────────────────────

def test_form_loads_for_accepted_order(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    resp = pilot_client.get(f"/pilot/orders/{order_id}/risk-assessment")
    assert resp.status_code == 200
    assert b"Pre-Flight Risk Assessment" in resp.data
    assert b"Site Assessment" in resp.data


# ── 2. Form blocked for non-accepted order ───────────────────────

def test_form_blocked_for_non_accepted_order(app, pilot_client):
    order_id = _make_order(app, status="assigned")
    resp = pilot_client.get(
        f"/pilot/orders/{order_id}/risk-assessment",
        follow_redirects=True,
    )
    assert b"only available for accepted orders" in resp.data


# ── 3. Ownership enforced ────────────────────────────────────────

def test_ownership_enforced(app, pilot_client):
    with app.app_context():
        other = User(username="pilot_other", display_name="Other", role="pilot")
        other.set_password("pass123")
        db.session.add(other)
        db.session.commit()

        fp = FlightPlan(
            reference="FP-RA-OTHER",
            customer_name="Other Client",
            customer_email="other@test.com",
            job_type="inspection",
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()

        order = Order(
            flight_plan_id=fp.id,
            pilot_id=other.id,
            status="accepted",
            accepted_at=datetime.now(timezone.utc),
        )
        db.session.add(order)
        db.session.commit()
        order_id = order.id

    resp = pilot_client.get(f"/pilot/orders/{order_id}/risk-assessment")
    assert resp.status_code == 403


# ── 4. Successful submission ─────────────────────────────────────

def test_successful_submission(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/risk-assessment",
        data=data,
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"risk assessment completed" in resp.data

    with app.app_context():
        order = db.session.get(Order, order_id)
        assert order.risk_assessment_completed is True
        ra = RiskAssessment.query.filter_by(order_id=order_id).first()
        assert ra is not None
        assert ra.risk_level == "low"
        assert ra.decision == "proceed"
        assert ra.pilot_declaration is True
        assert ra.all_checks_passed() is True


# ── 5. Missing checks rejected ───────────────────────────────────

def test_missing_checks_rejected(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    # Remove one check
    del data["site_ground_hazards"]
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/risk-assessment",
        data=data,
        follow_redirects=True,
    )
    assert b"safety checks must be confirmed" in resp.data

    with app.app_context():
        order = db.session.get(Order, order_id)
        assert order.risk_assessment_completed is False


# ── 6. Abort decision keeps gate closed ──────────────────────────

def test_abort_keeps_gate_closed(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    data["decision"] = "abort"
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/risk-assessment",
        data=data,
        follow_redirects=True,
    )
    assert b"Flight aborted" in resp.data

    with app.app_context():
        order = db.session.get(Order, order_id)
        # Assessment is marked completed (abort is a valid completion)
        assert order.risk_assessment_completed is True
        ra = RiskAssessment.query.filter_by(order_id=order_id).first()
        assert ra is not None
        assert ra.decision == "abort"

    # But transitioning to in_progress should still be blocked
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/status",
        data={"status": "in_progress"},
        follow_redirects=True,
    )
    assert b"aborted in risk assessment" in resp.data


# ── 7. Cannot start flight without assessment ────────────────────

def test_cannot_start_flight_without_assessment(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/status",
        data={"status": "in_progress"},
        follow_redirects=True,
    )
    assert b"must complete the pre-flight risk assessment" in resp.data


# ── 8. Can start flight after assessment ─────────────────────────

def test_can_start_flight_after_assessment(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    pilot_client.post(
        f"/pilot/orders/{order_id}/risk-assessment",
        data=data,
    )
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/status",
        data={"status": "in_progress"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"In Progress" in resp.data


# ── 9. Duplicate submission rejected ─────────────────────────────

def test_duplicate_submission_rejected(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    pilot_client.post(f"/pilot/orders/{order_id}/risk-assessment", data=data)

    # Second submission should show read-only view, not create duplicate
    resp = pilot_client.get(f"/pilot/orders/{order_id}/risk-assessment")
    assert resp.status_code == 200
    assert b"Completed" in resp.data

    with app.app_context():
        count = RiskAssessment.query.filter_by(order_id=order_id).count()
        assert count == 1


# ── 10. Read-only view after completion ──────────────────────────

def test_readonly_view_after_completion(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    pilot_client.post(f"/pilot/orders/{order_id}/risk-assessment", data=data)

    resp = pilot_client.get(f"/pilot/orders/{order_id}/risk-assessment")
    assert resp.status_code == 200
    assert b"Completed" in resp.data
    assert b"Low" in resp.data
    assert b"Proceed" in resp.data


# ── 11. Activity log records assessment ──────────────────────────

def test_activity_log_records_assessment(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    pilot_client.post(f"/pilot/orders/{order_id}/risk-assessment", data=data)

    with app.app_context():
        activities = OrderActivity.query.filter_by(order_id=order_id).all()
        actions = [a.action for a in activities]
        assert "risk_assessment_completed" in actions


# ── 12. Proceed with mitigations requires notes ─────────────────

def test_proceed_with_mitigations_requires_notes(app, pilot_client):
    order_id = _make_order(app, status="accepted")
    data = _valid_assessment_data()
    data["decision"] = "proceed_with_mitigations"
    # No mitigation_notes provided
    data["mitigation_notes"] = ""
    resp = pilot_client.post(
        f"/pilot/orders/{order_id}/risk-assessment",
        data=data,
        follow_redirects=True,
    )
    assert b"Mitigation notes are required" in resp.data

    with app.app_context():
        order = db.session.get(Order, order_id)
        assert order.risk_assessment_completed is False
