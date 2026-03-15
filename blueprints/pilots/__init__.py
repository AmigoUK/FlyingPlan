from flask import Blueprint

pilots_bp = Blueprint("pilots", __name__, template_folder="../../templates")

from blueprints.pilots import routes  # noqa: E402, F401
