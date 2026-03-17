"""Tests for v1.12 Mission Sharing & Export Formats."""
import io
import json
import xml.etree.ElementTree as ET
import pytest
from app import create_app
from extensions import db
from models.flight_plan import FlightPlan
from models.waypoint import Waypoint
from models.shared_link import SharedLink
from services.export_formats import generate_kml, generate_geojson, generate_csv, generate_gpx


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
def flight_plan(app):
    with app.app_context():
        fp = FlightPlan(
            reference="FP-EXPORT-TEST",
            customer_name="Test",
            customer_email="t@t.com",
            job_type="survey",
            location_lat=51.5074,
            location_lng=-0.1278,
            consent_given=True,
        )
        db.session.add(fp)
        db.session.flush()
        wps = [
            Waypoint(flight_plan_id=fp.id, index=0, lat=51.5074, lng=-0.1278,
                     altitude_m=30.0, speed_ms=5.0, gimbal_pitch_deg=-90.0),
            Waypoint(flight_plan_id=fp.id, index=1, lat=51.508, lng=-0.127,
                     altitude_m=40.0, speed_ms=4.0, gimbal_pitch_deg=-45.0),
        ]
        db.session.add_all(wps)
        db.session.commit()
        return fp


def test_generate_kml(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_kml(fp)
        content = buf.read().decode("utf-8")
        assert "FP-EXPORT-TEST" in content
        assert "WP 0" in content
        root = ET.fromstring(content)
        assert root.tag.endswith("kml")


def test_generate_geojson(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_geojson(fp)
        data = json.loads(buf.read())
        assert data["type"] == "FeatureCollection"
        assert len(data["features"]) == 3  # 1 route + 2 points


def test_generate_csv(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_csv(fp)
        content = buf.read().decode("utf-8")
        lines = content.strip().split("\n")
        assert len(lines) == 3  # header + 2 rows
        assert "index,lat,lng" in lines[0]


def test_generate_gpx(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        buf = generate_gpx(fp)
        content = buf.read().decode("utf-8")
        root = ET.fromstring(content)
        assert root.tag.endswith("gpx")


def test_share_link_creation(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.post(f"/admin/{fp.id}/share", json={"expires_days": 7})
        assert resp.status_code == 200
        data = resp.get_json()
        assert data["success"] is True
        assert "/shared/" in data["url"]
        assert data["token"]


def test_shared_link_view(app, flight_plan):
    with app.app_context():
        fp = db.session.merge(flight_plan)
        link = SharedLink(flight_plan_id=fp.id)
        link.generate_token()
        db.session.add(link)
        db.session.commit()

        client = app.test_client()
        resp = client.get(f"/shared/{link.token}")
        assert resp.status_code == 200
        assert b"FP-EXPORT-TEST" in resp.data


def test_shared_link_expired(app, flight_plan):
    from datetime import datetime, timezone, timedelta
    with app.app_context():
        fp = db.session.merge(flight_plan)
        link = SharedLink(
            flight_plan_id=fp.id,
            expires_at=datetime.now(timezone.utc) - timedelta(days=1),
        )
        link.generate_token()
        db.session.add(link)
        db.session.commit()

        client = app.test_client()
        resp = client.get(f"/shared/{link.token}")
        assert resp.status_code == 410


def test_export_kml_route(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.get(f"/admin/{fp.id}/export-kml")
        assert resp.status_code == 200


def test_export_csv_route(app, flight_plan):
    client = app.test_client()
    client.post("/login", data={"username": "admin", "password": "admin123"})
    with app.app_context():
        fp = db.session.merge(flight_plan)
        resp = client.get(f"/admin/{fp.id}/export-csv")
        assert resp.status_code == 200
        assert b"index,lat,lng" in resp.data
