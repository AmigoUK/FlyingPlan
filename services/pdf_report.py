"""PDF report generator for Flying Event Reports."""
import io
from datetime import datetime, timezone
from flask import render_template
from weasyprint import HTML

from services.static_map import generate_static_map_data_uri


def generate_report_pdf(order, include_admin_notes=False):
    """Generate a PDF report for an order. Returns a BytesIO buffer."""
    fp = order.flight_plan
    waypoints = sorted(fp.waypoints, key=lambda w: w.index)
    ra = order.risk_assessment
    activities = sorted(order.activity_log, key=lambda a: a.created_at)
    map_data_uri = generate_static_map_data_uri(fp)

    html = render_template(
        "reports/flight_report.html",
        order=order,
        fp=fp,
        waypoints=waypoints,
        ra=ra,
        activities=activities,
        include_admin_notes=include_admin_notes,
        map_data_uri=map_data_uri,
        now=lambda: datetime.now(timezone.utc),
    )

    pdf_bytes = HTML(string=html).write_pdf()
    buf = io.BytesIO(pdf_bytes)
    buf.seek(0)
    return buf
