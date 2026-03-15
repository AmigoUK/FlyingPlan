import pytest
from app import create_app
from extensions import db
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
def pilot_user(app):
    with app.app_context():
        pilot = User.query.filter_by(username="pilot1").first()
        return pilot.id


def _login(client, username, password):
    return client.post("/login", data={
        "username": username, "password": password
    }, follow_redirects=True)


# ── Role model tests ────────────────────────────────────────────

def test_has_role_at_least(app):
    with app.app_context():
        admin = User.query.filter_by(username="admin").first()
        assert admin.has_role_at_least("admin")
        assert admin.has_role_at_least("manager")
        assert admin.has_role_at_least("pilot")

        pilot = User.query.filter_by(username="pilot1").first()
        assert pilot.has_role_at_least("pilot")
        assert not pilot.has_role_at_least("manager")
        assert not pilot.has_role_at_least("admin")


def test_is_pilot_property(app):
    with app.app_context():
        pilot = User.query.filter_by(username="pilot1").first()
        assert pilot.is_pilot
        admin = User.query.filter_by(username="admin").first()
        assert not admin.is_pilot


def test_is_manager_or_above(app):
    with app.app_context():
        admin = User.query.filter_by(username="admin").first()
        assert admin.is_manager_or_above
        pilot = User.query.filter_by(username="pilot1").first()
        assert not pilot.is_manager_or_above


# ── Decorator enforcement ───────────────────────────────────────

def test_pilot_cannot_access_admin_dashboard(client, pilot_user):
    _login(client, "pilot1", "pilot123")
    resp = client.get("/admin/", follow_redirects=False)
    assert resp.status_code == 403


def test_pilot_cannot_access_settings(client, pilot_user):
    _login(client, "pilot1", "pilot123")
    resp = client.get("/admin/settings/", follow_redirects=False)
    assert resp.status_code == 403


def test_admin_can_access_admin_dashboard(client):
    _login(client, "admin", "admin123")
    resp = client.get("/admin/")
    assert resp.status_code == 200


def test_admin_can_access_settings(client):
    _login(client, "admin", "admin123")
    resp = client.get("/admin/settings/")
    assert resp.status_code == 200


# ── Login redirect ──────────────────────────────────────────────

def test_pilot_login_redirects_to_pilot_dashboard(client, pilot_user):
    resp = client.post("/login", data={
        "username": "pilot1", "password": "pilot123"
    }, follow_redirects=False)
    assert resp.status_code == 302
    assert "/pilot/" in resp.location


def test_admin_login_redirects_to_admin_dashboard(client):
    resp = client.post("/login", data={
        "username": "admin", "password": "admin123"
    }, follow_redirects=False)
    assert resp.status_code == 302
    assert "/admin" in resp.location


# ── Pilot can access pilot routes ───────────────────────────────

def test_pilot_can_access_pilot_dashboard(client, pilot_user):
    _login(client, "pilot1", "pilot123")
    resp = client.get("/pilot/")
    assert resp.status_code == 200


def test_pilot_can_access_pilot_profile(client, pilot_user):
    _login(client, "pilot1", "pilot123")
    resp = client.get("/pilot/profile")
    assert resp.status_code == 200


# ── Seeded roles ────────────────────────────────────────────────

def test_seeded_admin_has_admin_role(app):
    with app.app_context():
        admin = User.query.filter_by(username="admin").first()
        assert admin.role == "admin"


def test_seeded_pilot_has_pilot_role(app):
    with app.app_context():
        pilot = User.query.filter_by(username="pilot1").first()
        assert pilot.role == "pilot"
        assert pilot.flying_id == "CAA-12345"
        assert pilot.operator_id == "REOC-6789"
