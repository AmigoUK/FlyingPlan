import json
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint


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
            reference="FP-20260315-9999",
            customer_name="Test Client",
            customer_email="test@test.com",
            job_type="inspection",
            job_description="Test job",
            location_lat=-33.87,
            location_lng=151.21,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.commit()
        return fp.id


def test_dashboard_requires_login(client):
    resp = client.get("/admin/", follow_redirects=False)
    assert resp.status_code == 302
    assert "/login" in resp.location


def test_login(client):
    resp = client.post("/login", data={
        "username": "admin",
        "password": "admin123",
    }, follow_redirects=True)
    assert resp.status_code == 200


def test_dashboard_loads(logged_in_client, sample_plan):
    resp = logged_in_client.get("/admin/")
    assert resp.status_code == 200
    assert b"FP-20260315-9999" in resp.data


def test_detail_loads(logged_in_client, sample_plan):
    resp = logged_in_client.get(f"/admin/{sample_plan}")
    assert resp.status_code == 200
    assert b"Test Client" in resp.data


def test_save_waypoints(logged_in_client, sample_plan):
    waypoints = [
        {"lat": -33.86, "lng": 151.20, "altitude_m": 30, "speed_ms": 5},
        {"lat": -33.861, "lng": 151.201, "altitude_m": 50, "speed_ms": 3},
    ]
    resp = logged_in_client.post(
        f"/admin/{sample_plan}/waypoints",
        data=json.dumps(waypoints),
        content_type="application/json",
    )
    data = resp.get_json()
    assert data["success"] is True
    assert data["count"] == 2


def test_update_status(logged_in_client, sample_plan):
    resp = logged_in_client.post(
        f"/admin/{sample_plan}/status",
        data=json.dumps({"status": "in_review"}),
        content_type="application/json",
    )
    data = resp.get_json()
    assert data["success"] is True
    assert data["status"] == "in_review"


def test_save_notes(logged_in_client, sample_plan):
    resp = logged_in_client.post(
        f"/admin/{sample_plan}/notes",
        data=json.dumps({"notes": "Fly low near trees"}),
        content_type="application/json",
    )
    data = resp.get_json()
    assert data["success"] is True


def test_dashboard_filter_status(logged_in_client, sample_plan):
    resp = logged_in_client.get("/admin/?status=new")
    assert resp.status_code == 200
    assert b"FP-20260315-9999" in resp.data


def test_dashboard_search(logged_in_client, sample_plan):
    resp = logged_in_client.get("/admin/?q=Test+Client")
    assert resp.status_code == 200
    assert b"FP-20260315-9999" in resp.data
