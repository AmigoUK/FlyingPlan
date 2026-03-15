import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.order import Order
from models.order_activity import OrderActivity
from models.user import User


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
def logged_in_client(client):
    client.post("/login", data={"username": "admin", "password": "admin123"})
    return client


@pytest.fixture
def sample_plan(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-20260315-8888",
            customer_name="Order Test Client",
            customer_email="order@test.com",
            job_type="inspection",
            job_description="Test order job",
            location_lat=-33.87,
            location_lng=151.21,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()
        return fp.id


@pytest.fixture
def pilot_id(app):
    with app.app_context():
        pilot = User.query.filter_by(username="pilot1").first()
        return pilot.id


# ── Create order ────────────────────────────────────────────────

def test_create_order(logged_in_client, sample_plan, pilot_id):
    resp = logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id, "scheduled_date": "2026-04-01"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Order created" in resp.data


def test_create_order_without_pilot(logged_in_client, sample_plan):
    resp = logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Order created" in resp.data


def test_duplicate_order_prevented(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    resp = logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
        follow_redirects=True,
    )
    assert b"already exists" in resp.data


# ── Assign pilot ────────────────────────────────────────────────

def test_assign_pilot(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(f"/admin/orders/create/{sample_plan}", data={})
    with logged_in_client.application.app_context():
        order = Order.query.filter_by(flight_plan_id=sample_plan).first()

    resp = logged_in_client.post(
        f"/admin/orders/{order.id}/assign",
        data={"pilot_id": pilot_id},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"assigned" in resp.data.lower()


# ── Status transitions ─────────────────────────────────────────

def test_status_update(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    with logged_in_client.application.app_context():
        order = Order.query.filter_by(flight_plan_id=sample_plan).first()

    resp = logged_in_client.post(
        f"/admin/orders/{order.id}/status",
        data={"status": "accepted"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Accepted" in resp.data


def test_invalid_status_rejected(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    with logged_in_client.application.app_context():
        order = Order.query.filter_by(flight_plan_id=sample_plan).first()

    resp = logged_in_client.post(
        f"/admin/orders/{order.id}/status",
        data={"status": "bogus"},
        follow_redirects=True,
    )
    assert b"Invalid status" in resp.data


# ── Activity log ────────────────────────────────────────────────

def test_activity_log_created(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    with logged_in_client.application.app_context():
        order = Order.query.filter_by(flight_plan_id=sample_plan).first()
        activities = OrderActivity.query.filter_by(order_id=order.id).all()
        actions = [a.action for a in activities]
        assert "created" in actions
        assert "assigned" in actions


# ── Orders list ─────────────────────────────────────────────────

def test_orders_list(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    resp = logged_in_client.get("/admin/orders/")
    assert resp.status_code == 200
    assert b"FP-20260315-8888" in resp.data


def test_orders_filter_by_status(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    resp = logged_in_client.get("/admin/orders/?status=assigned")
    assert resp.status_code == 200
    assert b"FP-20260315-8888" in resp.data


# ── Admin notes ─────────────────────────────────────────────────

def test_save_order_notes(logged_in_client, sample_plan, pilot_id):
    logged_in_client.post(
        f"/admin/orders/create/{sample_plan}",
        data={"pilot_id": pilot_id},
    )
    with logged_in_client.application.app_context():
        order = Order.query.filter_by(flight_plan_id=sample_plan).first()

    resp = logged_in_client.post(
        f"/admin/orders/{order.id}/notes",
        data={"assignment_notes": "Fly carefully near power lines"},
        follow_redirects=True,
    )
    assert resp.status_code == 200
    assert b"Notes saved" in resp.data
