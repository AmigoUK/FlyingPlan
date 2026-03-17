import json
from flask import render_template, abort
from blueprints.shared import shared_bp
from extensions import db
from models.shared_link import SharedLink


@shared_bp.route("/<token>")
def mission_view(token):
    link = SharedLink.query.filter_by(token=token).first_or_404()
    if not link.is_valid:
        abort(410)  # Gone

    fp = link.flight_plan
    waypoints_json = json.dumps([w.to_dict() for w in fp.waypoints])
    pois_json = json.dumps(
        [{"lat": p.lat, "lng": p.lng, "label": p.label} for p in fp.pois]
    )

    # Route stats
    total_distance = 0
    from math import radians, sin, cos, sqrt, atan2
    wps = sorted(fp.waypoints, key=lambda w: w.index)
    for i in range(1, len(wps)):
        dlat = radians(wps[i].lat - wps[i - 1].lat)
        dlng = radians(wps[i].lng - wps[i - 1].lng)
        a = sin(dlat / 2) ** 2 + cos(radians(wps[i - 1].lat)) * cos(radians(wps[i].lat)) * sin(dlng / 2) ** 2
        total_distance += 6371000 * 2 * atan2(sqrt(a), sqrt(1 - a))

    stats = {
        "waypoint_count": len(wps),
        "total_distance_m": round(total_distance),
        "total_distance_km": round(total_distance / 1000, 2),
    }

    return render_template(
        "shared/mission_view.html",
        fp=fp,
        waypoints_json=waypoints_json,
        pois_json=pois_json,
        stats=stats,
    )
