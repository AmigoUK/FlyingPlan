"""
KMZ generator for DJI compatible waypoint missions.
Produces a .kmz (ZIP) containing:
  wpmz/template.kml  - Mission template for DJI Fly UI
  wpmz/waylines.wpml - Executable flight instructions

Supports multiple drone models via drone_profiles.
"""
import io
import zipfile
import xml.etree.ElementTree as ET

from services.drone_profiles import get_profile, DEFAULT_DRONE

WPML_NS = "http://www.dji.com/wpmz/1.0.6"
KML_NS = "http://www.opengis.net/kml/2.2"


def generate_kmz(flight_plan, drone_model=None):
    """Generate a KMZ file buffer from a FlightPlan with waypoints."""
    model = drone_model or getattr(flight_plan, "drone_model", None) or DEFAULT_DRONE
    profile = get_profile(model)
    waypoints = sorted(flight_plan.waypoints, key=lambda w: w.index)

    template_kml = _build_template_kml(flight_plan, waypoints, profile)
    waylines_wpml = _build_waylines_wpml(flight_plan, waypoints, profile)

    buf = io.BytesIO()
    with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as zf:
        zf.writestr("wpmz/template.kml", template_kml)
        zf.writestr("wpmz/waylines.wpml", waylines_wpml)

    buf.seek(0)
    return buf


def _build_template_kml(flight_plan, waypoints, profile):
    """Build the template.kml for DJI Fly UI display."""
    ET.register_namespace("", KML_NS)
    ET.register_namespace("wpml", WPML_NS)

    kml = ET.Element("{%s}kml" % KML_NS)
    document = ET.SubElement(kml, "{%s}Document" % KML_NS)

    # Mission config
    _add_wpml(document, "missionConfig", None)
    mc = document.find("{%s}missionConfig" % WPML_NS)
    _add_wpml(mc, "flyToWaylineMode", "safely")
    _add_wpml(mc, "finishAction", "goHome")
    _add_wpml(mc, "exitOnRCLost", "executeLostAction")
    _add_wpml(mc, "executeRCLostAction", "goBack")
    _add_wpml(mc, "globalTransitionalSpeed", "5.0")

    # Drone info from profile
    drone_info = ET.SubElement(mc, "{%s}droneInfo" % WPML_NS)
    _add_wpml(drone_info, "droneEnumValue", str(profile["droneEnumValue"]))
    _add_wpml(drone_info, "droneSubEnumValue", str(profile["droneSubEnumValue"]))

    # Payload info
    payload_info = ET.SubElement(mc, "{%s}payloadInfo" % WPML_NS)
    _add_wpml(payload_info, "payloadEnumValue", str(profile["payloadEnumValue"]))
    _add_wpml(payload_info, "payloadSubEnumValue", "0")
    _add_wpml(payload_info, "payloadPositionIndex", "0")

    # Folder with placemarks
    folder = ET.SubElement(document, "{%s}Folder" % KML_NS)
    _add_wpml(folder, "templateType", "waypoint")
    _add_wpml(folder, "templateId", "0")

    _add_wpml(folder, "autoFlightSpeed", "5.0")
    _add_wpml(folder, "waylineCoordinateSysParam", None)
    coord_sys = folder.find("{%s}waylineCoordinateSysParam" % WPML_NS)
    _add_wpml(coord_sys, "coordinateMode", "WGS84")
    _add_wpml(coord_sys, "heightMode", "relativeToStartPoint")

    for wp in waypoints:
        pm = ET.SubElement(folder, "{%s}Placemark" % KML_NS)
        point = ET.SubElement(pm, "{%s}Point" % KML_NS)
        coords = ET.SubElement(point, "{%s}coordinates" % KML_NS)
        coords.text = "%.7f,%.7f" % (wp.lng, wp.lat)

        _add_wpml(pm, "index", str(wp.index))
        _add_wpml(pm, "executeHeight", "%.1f" % wp.altitude_m)
        _add_wpml(pm, "waypointSpeed", "%.1f" % wp.speed_ms)

        # Heading
        heading_param = ET.SubElement(pm, "{%s}waypointHeadingParam" % WPML_NS)
        if wp.heading_deg is not None:
            _add_wpml(heading_param, "waypointHeadingMode", "smoothTransition")
            _add_wpml(heading_param, "waypointHeadingAngle", "%.1f" % wp.heading_deg)
        else:
            _add_wpml(heading_param, "waypointHeadingMode", "followWayline")

        # Turn param
        turn_param = ET.SubElement(pm, "{%s}waypointTurnParam" % WPML_NS)
        _add_wpml(turn_param, "waypointTurnMode", wp.turn_mode)
        _add_wpml(turn_param, "waypointTurnDampingDist", "%.1f" % wp.turn_damping_dist)

    tree = ET.ElementTree(kml)
    buf = io.BytesIO()
    tree.write(buf, xml_declaration=True, encoding="UTF-8")
    return buf.getvalue().decode("UTF-8")


def _build_waylines_wpml(flight_plan, waypoints, profile):
    """Build the waylines.wpml executable flight instructions."""
    ET.register_namespace("", KML_NS)
    ET.register_namespace("wpml", WPML_NS)

    kml = ET.Element("{%s}kml" % KML_NS)
    document = ET.SubElement(kml, "{%s}Document" % KML_NS)

    # Mission config (same as template)
    _add_wpml(document, "missionConfig", None)
    mc = document.find("{%s}missionConfig" % WPML_NS)
    _add_wpml(mc, "flyToWaylineMode", "safely")
    _add_wpml(mc, "finishAction", "goHome")
    _add_wpml(mc, "exitOnRCLost", "executeLostAction")
    _add_wpml(mc, "executeRCLostAction", "goBack")
    _add_wpml(mc, "globalTransitionalSpeed", "5.0")

    drone_info = ET.SubElement(mc, "{%s}droneInfo" % WPML_NS)
    _add_wpml(drone_info, "droneEnumValue", str(profile["droneEnumValue"]))
    _add_wpml(drone_info, "droneSubEnumValue", str(profile["droneSubEnumValue"]))

    # Folder
    folder = ET.SubElement(document, "{%s}Folder" % KML_NS)
    _add_wpml(folder, "templateId", "0")
    _add_wpml(folder, "waylineId", "0")
    _add_wpml(folder, "distance", "0")
    _add_wpml(folder, "duration", "0")
    _add_wpml(folder, "autoFlightSpeed", "5.0")

    action_group_id = 0

    for wp in waypoints:
        pm = ET.SubElement(folder, "{%s}Placemark" % KML_NS)
        point = ET.SubElement(pm, "{%s}Point" % KML_NS)
        coords = ET.SubElement(point, "{%s}coordinates" % KML_NS)
        coords.text = "%.7f,%.7f" % (wp.lng, wp.lat)

        _add_wpml(pm, "index", str(wp.index))
        _add_wpml(pm, "executeHeight", "%.1f" % wp.altitude_m)
        _add_wpml(pm, "waypointSpeed", "%.1f" % wp.speed_ms)

        # Heading
        heading_param = ET.SubElement(pm, "{%s}waypointHeadingParam" % WPML_NS)
        if wp.heading_deg is not None:
            _add_wpml(heading_param, "waypointHeadingMode", "smoothTransition")
            _add_wpml(heading_param, "waypointHeadingAngle", "%.1f" % wp.heading_deg)
        else:
            _add_wpml(heading_param, "waypointHeadingMode", "followWayline")

        # Turn param
        turn_param = ET.SubElement(pm, "{%s}waypointTurnParam" % WPML_NS)
        _add_wpml(turn_param, "waypointTurnMode", wp.turn_mode)
        _add_wpml(turn_param, "waypointTurnDampingDist", "%.1f" % wp.turn_damping_dist)

        # Action group: gimbal + optional action
        action_group = ET.SubElement(pm, "{%s}actionGroup" % WPML_NS)
        _add_wpml(action_group, "actionGroupId", str(action_group_id))
        _add_wpml(action_group, "actionGroupStartIndex", str(wp.index))
        _add_wpml(action_group, "actionGroupEndIndex", str(wp.index))
        _add_wpml(action_group, "actionGroupMode", "sequence")
        _add_wpml(action_group, "actionTrigger", None)
        trigger = action_group.find("{%s}actionTrigger" % WPML_NS)
        _add_wpml(trigger, "actionTriggerType", "reachPoint")

        action_idx = 0

        # Gimbal rotate action
        gimbal_action = ET.SubElement(action_group, "{%s}action" % WPML_NS)
        _add_wpml(gimbal_action, "actionId", str(action_idx))
        _add_wpml(gimbal_action, "actionActuatorFunc", "gimbalRotate")
        gimbal_params = ET.SubElement(gimbal_action, "{%s}actionActuatorFuncParam" % WPML_NS)
        _add_wpml(gimbal_params, "gimbalRotateMode", "absoluteAngle")
        _add_wpml(gimbal_params, "gimbalPitchRotateEnable", "1")
        _add_wpml(gimbal_params, "gimbalPitchRotateAngle", "%.1f" % wp.gimbal_pitch_deg)
        _add_wpml(gimbal_params, "gimbalRollRotateEnable", "0")
        _add_wpml(gimbal_params, "gimbalRollRotateAngle", "0.0")
        _add_wpml(gimbal_params, "gimbalYawRotateEnable", "0")
        _add_wpml(gimbal_params, "gimbalYawRotateAngle", "0.0")
        _add_wpml(gimbal_params, "gimbalRotateTimeEnable", "0")
        _add_wpml(gimbal_params, "gimbalRotateTime", "0")
        _add_wpml(gimbal_params, "payloadPositionIndex", "0")
        action_idx += 1

        # Hover action
        if wp.hover_time_s and wp.hover_time_s > 0:
            hover_action = ET.SubElement(action_group, "{%s}action" % WPML_NS)
            _add_wpml(hover_action, "actionId", str(action_idx))
            _add_wpml(hover_action, "actionActuatorFunc", "hover")
            hover_params = ET.SubElement(hover_action, "{%s}actionActuatorFuncParam" % WPML_NS)
            _add_wpml(hover_params, "hoverTime", "%.1f" % wp.hover_time_s)
            action_idx += 1

        # Camera action
        if wp.action_type:
            cam_action = ET.SubElement(action_group, "{%s}action" % WPML_NS)
            _add_wpml(cam_action, "actionId", str(action_idx))
            _add_wpml(cam_action, "actionActuatorFunc", wp.action_type)
            cam_params = ET.SubElement(cam_action, "{%s}actionActuatorFuncParam" % WPML_NS)
            _add_wpml(cam_params, "payloadPositionIndex", "0")
            if wp.action_type == "takePhoto":
                _add_wpml(cam_params, "fileSuffix", "photo")
                _add_wpml(cam_params, "payloadLensIndex", "wide")
            action_idx += 1

        action_group_id += 1

    tree = ET.ElementTree(kml)
    buf = io.BytesIO()
    tree.write(buf, xml_declaration=True, encoding="UTF-8")
    return buf.getvalue().decode("UTF-8")


def _add_wpml(parent, tag, text):
    """Add a child element with wpml namespace."""
    elem = ET.SubElement(parent, "{%s}%s" % (WPML_NS, tag))
    if text is not None:
        elem.text = text
    return elem
