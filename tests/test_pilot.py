import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.order import Order
from models.user import User
from models.pilot_certification import PilotCertification
from models.pilot_equipment import PilotEquipment
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


@pytest.fixture
def assigned_order(app):
    """Create a flight plan and order assigned to pilot1 directly in DB."""
    from datetime import timezone
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260315-7777",
            customer_name="Pilot Test Client",
            customer_email="pilot_test@test.com",
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
            status="assigned",
            assigned_at=datetime.now(timezone.utc),
        )
        db.session.add(order)
        db.session.commit()
        return order.id


# ── Dashboard ───────────────────────────────────────────────────

def test_pilot_dashboard_loads(pilot_client):
    resp = pilot_client.get("/pilot/")
    assert resp.status_code == 200
    assert b"My Orders" in resp.data


def test_pilot_dashboard_shows_order(app, pilot_client, assigned_order):
    resp = pilot_client.get("/pilot/")
    assert resp.status_code == 200
    assert b"FP-20260315-7777" in resp.data


# ── Accept / Decline ────────────────────────────────────────────

def test_accept_order(pilot_client, assigned_order):
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/accept",
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Order accepted" in resp.data


def test_decline_order(pilot_client, assigned_order):
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/decline",
        data={"reason": "Schedule conflict"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Order declined" in resp.data


def test_cannot_accept_already_accepted(app, pilot_client, assigned_order):
    pilot_client.post(f"/pilot/orders/{assigned_order}/accept")
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/accept",
        follow_redirects=True,
    )
    assert b"Cannot accept" in resp.data


# ── Status updates ──────────────────────────────────────────────

def test_advance_status(pilot_client, assigned_order):
    pilot_client.post(f"/pilot/orders/{assigned_order}/accept")
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/status",
        data={"status": "in_progress"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"In Progress" in resp.data


def test_cannot_skip_status(pilot_client, assigned_order):
    pilot_client.post(f"/pilot/orders/{assigned_order}/accept")
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/status",
        data={"status": "delivered"},
        follow_redirects=True,
    )
    assert b"Invalid status" in resp.data


# ── Ownership enforcement ──────────────────────────────────────

def test_pilot_cannot_view_other_pilot_order(app, pilot_client):
    """Create an order assigned to a different pilot."""
    with app.app_context():
        other = User(username="pilot2", display_name="Other Pilot", role="pilot")
        other.set_password("pass123")
        db.session.add(other)
        db.session.commit()

        fp = FlightPlan(
            reference="FP-20260315-6666",
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
            status="assigned",
            assigned_at=datetime.now(timezone.utc),
        )
        db.session.add(order)
        db.session.commit()
        order_id = order.id

    resp = pilot_client.get(f"/pilot/orders/{order_id}")
    assert resp.status_code == 403


# ── Profile ─────────────────────────────────────────────────────

def test_profile_loads(pilot_client):
    resp = pilot_client.get("/pilot/profile")
    assert resp.status_code == 200
    assert b"My Profile" in resp.data


def test_update_profile(pilot_client):
    resp = pilot_client.post("/pilot/profile", data={
        "display_name": "Updated Pilot",
        "email": "updated@test.com",
        "flying_id": "CAA-99999",
    }, follow_redirects=True)
    assert resp.status_code == 200
    assert b"Profile updated" in resp.data


# ── Certifications ──────────────────────────────────────────────

def test_add_certification(pilot_client, app):
    resp = pilot_client.post("/pilot/certifications/add", data={
        "cert_name": "RePL",
        "issuing_body": "CASA",
        "cert_number": "REPL-001",
    }, follow_redirects=True)
    assert resp.status_code == 200
    assert b"Certification added" in resp.data

    with app.app_context():
        pilot = User.query.filter_by(username="pilot1").first()
        assert len(pilot.certifications) == 1


def test_delete_certification(pilot_client, app):
    pilot_client.post("/pilot/certifications/add", data={
        "cert_name": "TempCert",
    })
    with app.app_context():
        cert = PilotCertification.query.filter_by(cert_name="TempCert").first()
        cert_id = cert.id

    resp = pilot_client.post(
        f"/pilot/certifications/{cert_id}/delete",
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Certification deleted" in resp.data


# ── Equipment ───────────────────────────────────────────────────

def test_add_equipment(pilot_client, app):
    resp = pilot_client.post("/pilot/equipment/add", data={
        "drone_model": "DJI Mini 4 Pro",
        "serial_number": "SN-12345",
    }, follow_redirects=True)
    assert resp.status_code == 200
    assert b"Equipment added" in resp.data


def test_delete_equipment(pilot_client, app):
    pilot_client.post("/pilot/equipment/add", data={
        "drone_model": "TempDrone",
    })
    with app.app_context():
        equip = PilotEquipment.query.filter_by(drone_model="TempDrone").first()
        equip_id = equip.id

    resp = pilot_client.post(
        f"/pilot/equipment/{equip_id}/delete",
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Equipment removed" in resp.data


# ── Pilot Notes ─────────────────────────────────────────────────

def test_save_pilot_notes(pilot_client, assigned_order):
    resp = pilot_client.post(
        f"/pilot/orders/{assigned_order}/notes",
        data={"pilot_notes": "Wind conditions look good"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Notes saved" in resp.data
