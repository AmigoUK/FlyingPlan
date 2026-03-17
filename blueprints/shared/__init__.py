from flask import Blueprint

shared_bp = Blueprint("shared", __name__)

from blueprints.shared import routes  # noqa: E402, F401
