from flask import render_template

from blueprints.help import help_bp


@help_bp.route("/")
def index():
    return render_template("help/index.html")
