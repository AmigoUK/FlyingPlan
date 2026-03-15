import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan


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


def test_form_page_loads(client):
    resp = client.get("/")
    assert resp.status_code == 200
    assert b"Drone Flight Brief" in resp.data


def test_submit_valid(client, app):
    resp = client.post("/submit", data={
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "customer_phone": "0412345678",
        "job_type": "inspection",
        "job_description": "Roof inspection needed",
        "location_lat": "-33.8688",
        "location_lng": "151.2093",
        "location_address": "Sydney Opera House",
        "altitude_preset": "medium",
        "camera_angle": "45deg",
        "video_resolution": "4k",
        "photo_mode": "single",
        "consent_given": "1",
    }, follow_redirects=True)
    assert resp.status_code == 200
    assert b"Flight Brief Submitted" in resp.data

    with app.app_context():
        fp = FlightPlan.query.first()
        assert fp is not None
        assert fp.customer_name == "John Doe"
        assert fp.status == "new"


def test_submit_missing_fields(client, app):
    resp = client.post("/submit", data={
        "customer_name": "",
        "customer_email": "",
        "job_type": "",
        "consent_given": "",
    }, follow_redirects=True)
    assert resp.status_code == 200
    # Should redirect back to form with errors
    with app.app_context():
        assert FlightPlan.query.count() == 0


def test_submit_with_pois(client, app):
    import json
    resp = client.post("/submit", data={
        "customer_name": "Jane",
        "customer_email": "jane@test.com",
        "job_type": "survey",
        "job_description": "Land survey",
        "location_lat": "-33.87",
        "location_lng": "151.21",
        "consent_given": "1",
        "pois_json": json.dumps([
            {"lat": -33.861, "lng": 151.201, "label": "Corner A"},
            {"lat": -33.862, "lng": 151.202, "label": "Corner B"},
        ]),
    }, follow_redirects=True)
    assert resp.status_code == 200

    with app.app_context():
        fp = FlightPlan.query.first()
        assert len(fp.pois) == 2
        assert fp.pois[0].label == "Corner A"


def test_confirmation_page(client):
    resp = client.get("/confirmation?ref=FP-20260315-0001")
    assert resp.status_code == 200
    assert b"FP-20260315-0001" in resp.data
