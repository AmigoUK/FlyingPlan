"""Generate a static map PNG for PDF flight reports."""
import base64
import io
import logging

from staticmap import StaticMap, CircleMarker, Line

logger = logging.getLogger(__name__)


def generate_static_map_data_uri(flight_plan):
    """Return a data:image/png;base64,... URI of the flight route map.

    Includes customer location (red), POI markers (orange),
    waypoint markers (green), and a route polyline.
    Returns None if no coordinates are available.
    """
    try:
        m = StaticMap(800, 500, url_template="https://tile.openstreetmap.org/{z}/{x}/{y}.png")
        has_points = False

        # Customer location — red marker
        if flight_plan.location_lat and flight_plan.location_lng:
            m.add_marker(
                CircleMarker(
                    (float(flight_plan.location_lng), float(flight_plan.location_lat)),
                    "red",
                    12,
                )
            )
            has_points = True

        # POI markers — orange
        if hasattr(flight_plan, "pois") and flight_plan.pois:
            for poi in flight_plan.pois:
                if poi.lat and poi.lng:
                    m.add_marker(
                        CircleMarker(
                            (float(poi.lng), float(poi.lat)),
                            "#ff8c00",
                            8,
                        )
                    )
                    has_points = True

        # Waypoints — green markers + route polyline
        waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)
        route_coords = []
        for wp in waypoints:
            if wp.lat and wp.lng:
                coord = (float(wp.lng), float(wp.lat))
                m.add_marker(CircleMarker(coord, "green", 8))
                route_coords.append(coord)
                has_points = True

        if len(route_coords) >= 2:
            m.add_line(Line(route_coords, "green", 3))

        if not has_points:
            return None

        image = m.render()
        buf = io.BytesIO()
        image.save(buf, format="PNG")
        b64 = base64.b64encode(buf.getvalue()).decode("ascii")
        return f"data:image/png;base64,{b64}"

    except Exception:
        logger.exception("Failed to generate static map for flight plan %s", flight_plan.id)
        return None
