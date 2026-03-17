from flask import Blueprint

help_bp = Blueprint("help", __name__)

from blueprints.help import routes  # noqa: E402, F401
