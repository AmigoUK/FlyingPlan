"""
Multi-format export: KML, GeoJSON, CSV, GPX.
Generates files from flight plan waypoints.
"""
import io
import json
import xml.etree.ElementTree as ET


def generate_kml(flight_plan):
    """Generate KML (Google Earth) from waypoints."""
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    kml_ns = "http://www.opengis.net/kml/2.2"
    ET.register_namespace("", kml_ns)

    kml = ET.Element("{%s}kml" % kml_ns)
    doc = ET.SubElement(kml, "{%s}Document" % kml_ns)
    name = ET.SubElement(doc, "{%s}name" % kml_ns)
    name.text = flight_plan.reference

    # Route line
    pm_line = ET.SubElement(doc, "{%s}Placemark" % kml_ns)
    pm_name = ET.SubElement(pm_line, "{%s}name" % kml_ns)
    pm_name.text = "Flight Route"
    line = ET.SubElement(pm_line, "{%s}LineString" % kml_ns)
    alt_mode = ET.SubElement(line, "{%s}altitudeMode" % kml_ns)
    alt_mode.text = "relativeToGround"
    coords = ET.SubElement(line, "{%s}coordinates" % kml_ns)
    coords.text = " ".join(
        "%.7f,%.7f,%.1f" % (w.lng, w.lat, w.altitude_m)
        for w in waypoints
    )

    # Individual waypoints
    for w in waypoints:
        pm = ET.SubElement(doc, "{%s}Placemark" % kml_ns)
        n = ET.SubElement(pm, "{%s}name" % kml_ns)
        n.text = "WP %d" % w.index
        desc = ET.SubElement(pm, "{%s}description" % kml_ns)
        desc.text = "Alt: %.1fm, Speed: %.1fm/s" % (w.altitude_m, w.speed_ms)
        point = ET.SubElement(pm, "{%s}Point" % kml_ns)
        am = ET.SubElement(point, "{%s}altitudeMode" % kml_ns)
        am.text = "relativeToGround"
        c = ET.SubElement(point, "{%s}coordinates" % kml_ns)
        c.text = "%.7f,%.7f,%.1f" % (w.lng, w.lat, w.altitude_m)

    tree = ET.ElementTree(kml)
    buf = io.BytesIO()
    tree.write(buf, xml_declaration=True, encoding="UTF-8")
    buf.seek(0)
    return buf


def generate_geojson(flight_plan):
    """Generate GeoJSON from waypoints."""
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    features = []

    # Route line
    if len(waypoints) >= 2:
        features.append({
            "type": "Feature",
            "properties": {"name": "Flight Route", "type": "route"},
            "geometry": {
                "type": "LineString",
                "coordinates": [[w.lng, w.lat, w.altitude_m] for w in waypoints],
            },
        })

    # Individual points
    for w in waypoints:
        features.append({
            "type": "Feature",
            "properties": {
                "name": "WP %d" % w.index,
                "altitude_m": w.altitude_m,
                "speed_ms": w.speed_ms,
                "heading_deg": w.heading_deg,
                "gimbal_pitch_deg": w.gimbal_pitch_deg,
                "action_type": w.action_type,
            },
            "geometry": {
                "type": "Point",
                "coordinates": [w.lng, w.lat, w.altitude_m],
            },
        })

    geojson = {
        "type": "FeatureCollection",
        "properties": {"reference": flight_plan.reference},
        "features": features,
    }

    buf = io.BytesIO(json.dumps(geojson, indent=2).encode("utf-8"))
    buf.seek(0)
    return buf


def generate_csv(flight_plan):
    """Generate CSV from waypoints."""
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)
    lines = ["index,lat,lng,altitude_m,speed_ms,heading_deg,gimbal_pitch_deg,turn_mode,hover_time_s,action_type"]
    for w in waypoints:
        lines.append(",".join([
            str(w.index),
            "%.7f" % w.lat,
            "%.7f" % w.lng,
            "%.1f" % w.altitude_m,
            "%.1f" % w.speed_ms,
            str(w.heading_deg) if w.heading_deg is not None else "",
            "%.1f" % w.gimbal_pitch_deg,
            w.turn_mode or "",
            "%.1f" % (w.hover_time_s or 0),
            w.action_type or "",
        ]))

    buf = io.BytesIO("\n".join(lines).encode("utf-8"))
    buf.seek(0)
    return buf


def generate_gpx(flight_plan):
    """Generate GPX (GPS Exchange Format) from waypoints."""
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    gpx_ns = "http://www.topografix.com/GPX/1/1"
    ET.register_namespace("", gpx_ns)

    gpx = ET.Element("{%s}gpx" % gpx_ns, version="1.1", creator="FlyingPlan")

    # Track
    trk = ET.SubElement(gpx, "{%s}trk" % gpx_ns)
    name = ET.SubElement(trk, "{%s}name" % gpx_ns)
    name.text = flight_plan.reference
    seg = ET.SubElement(trk, "{%s}trkseg" % gpx_ns)
    for w in waypoints:
        pt = ET.SubElement(seg, "{%s}trkpt" % gpx_ns, lat="%.7f" % w.lat, lon="%.7f" % w.lng)
        ele = ET.SubElement(pt, "{%s}ele" % gpx_ns)
        ele.text = "%.1f" % w.altitude_m
        n = ET.SubElement(pt, "{%s}name" % gpx_ns)
        n.text = "WP %d" % w.index

    # Waypoints
    for w in waypoints:
        wpt = ET.SubElement(gpx, "{%s}wpt" % gpx_ns, lat="%.7f" % w.lat, lon="%.7f" % w.lng)
        ele = ET.SubElement(wpt, "{%s}ele" % gpx_ns)
        ele.text = "%.1f" % w.altitude_m
        n = ET.SubElement(wpt, "{%s}name" % gpx_ns)
        n.text = "WP %d" % w.index

    tree = ET.ElementTree(gpx)
    buf = io.BytesIO()
    tree.write(buf, xml_declaration=True, encoding="UTF-8")
    buf.seek(0)
    return buf
