from flask import Blueprint

pilot_bp = Blueprint("pilot", __name__, template_folder="../../templates")

from blueprints.pilot import routes  # noqa: E402, F401
