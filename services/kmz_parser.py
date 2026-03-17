"""
KMZ parser for importing DJI waypoint mission files.
Extracts waypoints from wpmz/template.kml and/or wpmz/waylines.wpml.
Auto-detects drone model from drone profiles.
"""
import io
import zipfile
import xml.etree.ElementTree as ET
from services.drone_profiles import DRONE_PROFILES

WPML_NS = "http://www.dji.com/wpmz/1.0.6"
KML_NS = "http://www.opengis.net/kml/2.2"


def parse_kmz(file_bytes):
    """Parse a KMZ file and return waypoints + detected drone model.

    Args:
        file_bytes: bytes or BytesIO of the KMZ file

    Returns:
        dict with keys:
            waypoints: list of waypoint dicts
            drone_model: detected model key or None
            error: error string if parsing failed, else None
    """
    if isinstance(file_bytes, (bytes, bytearray)):
        buf = io.BytesIO(file_bytes)
    else:
        buf = file_bytes
        buf.seek(0)

    if not zipfile.is_zipfile(buf):
        return {"waypoints": [], "drone_model": None, "error": "Not a valid KMZ/ZIP file"}

    buf.seek(0)
    try:
        with zipfile.ZipFile(buf) as zf:
            names = zf.namelist()
            # Try waylines.wpml first (has more detail), fall back to template.kml
            if "wpmz/waylines.wpml" in names:
                content = zf.read("wpmz/waylines.wpml").decode("UTF-8")
                return _parse_wpml(content)
            elif "wpmz/template.kml" in names:
                content = zf.read("wpmz/template.kml").decode("UTF-8")
                return _parse_template_kml(content)
            else:
                return {"waypoints": [], "drone_model": None, "error": "No wpmz/template.kml or wpmz/waylines.wpml found"}
    except Exception as e:
        return {"waypoints": [], "drone_model": None, "error": str(e)}


def _parse_wpml(xml_content):
    """Parse waylines.wpml for detailed waypoint data."""
    ns = {"kml": KML_NS, "wpml": WPML_NS}
    root = ET.fromstring(xml_content)

    drone_model = _detect_drone(root, ns)
    waypoints = []

    placemarks = root.findall(".//kml:Placemark", ns)
    for pm in placemarks:
        wp = _extract_waypoint(pm, ns)
        if wp:
            # Extract action type from action groups
            action_groups = pm.findall("wpml:actionGroup", ns)
            for ag in action_groups:
                actions = ag.findall("wpml:action", ns)
                for action in actions:
                    func = _find_text(action, "wpml:actionActuatorFunc", ns)
                    if func and func not in ("gimbalRotate", "hover"):
                        wp["action_type"] = func
                        break
                    if func == "hover":
                        params = action.find("wpml:actionActuatorFuncParam", ns)
                        if params is not None:
                            ht = _find_text(params, "wpml:hoverTime", ns)
                            if ht:
                                wp["hover_time_s"] = float(ht)

            waypoints.append(wp)

    return {"waypoints": waypoints, "drone_model": drone_model, "error": None}


def _parse_template_kml(xml_content):
    """Parse template.kml for basic waypoint data."""
    ns = {"kml": KML_NS, "wpml": WPML_NS}
    root = ET.fromstring(xml_content)

    drone_model = _detect_drone(root, ns)
    waypoints = []

    placemarks = root.findall(".//kml:Placemark", ns)
    for pm in placemarks:
        wp = _extract_waypoint(pm, ns)
        if wp:
            waypoints.append(wp)

    return {"waypoints": waypoints, "drone_model": drone_model, "error": None}


def _extract_waypoint(placemark, ns):
    """Extract waypoint dict from a Placemark element."""
    coords_el = placemark.find(".//kml:coordinates", ns)
    if coords_el is None or not coords_el.text:
        return None

    parts = coords_el.text.strip().split(",")
    if len(parts) < 2:
        return None

    lng = float(parts[0])
    lat = float(parts[1])

    index_text = _find_text(placemark, "wpml:index", ns)
    index = int(index_text) if index_text else 0

    altitude = _find_float(placemark, "wpml:executeHeight", ns, 30.0)
    speed = _find_float(placemark, "wpml:waypointSpeed", ns, 5.0)

    # Heading
    heading = None
    hp = placemark.find("wpml:waypointHeadingParam", ns)
    if hp is not None:
        mode = _find_text(hp, "wpml:waypointHeadingMode", ns)
        if mode == "smoothTransition":
            heading = _find_float(hp, "wpml:waypointHeadingAngle", ns, None)

    # Turn mode
    turn_mode = "toPointAndStopWithDiscontinuityCurvature"
    tp = placemark.find("wpml:waypointTurnParam", ns)
    if tp is not None:
        tm = _find_text(tp, "wpml:waypointTurnMode", ns)
        if tm:
            turn_mode = tm

    # Gimbal pitch
    gimbal_pitch = -90.0

    return {
        "index": index,
        "lat": lat,
        "lng": lng,
        "altitude_m": altitude,
        "speed_ms": speed,
        "heading_deg": heading,
        "gimbal_pitch_deg": gimbal_pitch,
        "turn_mode": turn_mode,
        "turn_damping_dist": 0.0,
        "hover_time_s": 0.0,
        "action_type": None,
        "poi_lat": None,
        "poi_lng": None,
    }


def _detect_drone(root, ns):
    """Detect drone model from droneEnumValue in mission config."""
    drone_enum_el = root.find(".//wpml:droneEnumValue", ns)
    drone_sub_el = root.find(".//wpml:droneSubEnumValue", ns)

    if drone_enum_el is None:
        return None

    try:
        enum_val = int(drone_enum_el.text)
        sub_val = int(drone_sub_el.text) if drone_sub_el is not None else 0
    except (ValueError, TypeError):
        return None

    for key, profile in DRONE_PROFILES.items():
        if (profile["droneEnumValue"] == enum_val and
                profile["droneSubEnumValue"] == sub_val):
            return key

    # Fallback: match on enum only (ignore sub)
    for key, profile in DRONE_PROFILES.items():
        if profile["droneEnumValue"] == enum_val:
            return key

    return None


def _find_text(parent, path, ns):
    el = parent.find(path, ns)
    return el.text if el is not None and el.text else None


def _find_float(parent, path, ns, default):
    text = _find_text(parent, path, ns)
    if text:
        try:
            return float(text)
        except ValueError:
            pass
    return default
