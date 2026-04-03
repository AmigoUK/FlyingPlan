<?php

namespace App\Services;

/**
 * KMZ generator for DJI compatible waypoint missions.
 * Produces a .kmz (ZIP) containing:
 *   wpmz/template.kml  - Mission template for DJI Fly UI
 *   wpmz/waylines.wpml - Executable flight instructions
 *
 * Ported from services/kmz_generator.py.
 */
class KmzGenerator
{
    private const WPML_NS = 'http://www.dji.com/wpmz/1.0.6';
    private const KML_NS  = 'http://www.opengis.net/kml/2.2';

    /**
     * Generate a KMZ file string from waypoints array and drone model.
     *
     * @param array  $waypoints  Array of waypoint assoc arrays (sorted by index)
     * @param string $reference  Flight plan reference for naming
     * @param string $droneModel Drone model key
     * @return string Binary KMZ (ZIP) content
     */
    public static function generateKmz(array $waypoints, string $reference = '', string $droneModel = 'mini_4_pro'): string
    {
        $profile = DroneProfiles::getProfile($droneModel);

        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $templateKml = self::buildTemplateKml($waypoints, $profile);
        $waylinesWpml = self::buildWaylinesWpml($waypoints, $profile);

        $tmpFile = tempnam(sys_get_temp_dir(), 'kmz_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('wpmz/template.kml', $templateKml);
        $zip->addFromString('wpmz/waylines.wpml', $waylinesWpml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    private static function buildTemplateKml(array $waypoints, array $profile): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $kml = $doc->createElementNS(self::KML_NS, 'kml');
        $kml->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wpml', self::WPML_NS);
        $doc->appendChild($kml);

        $document = self::kmlEl($doc, $kml, 'Document');

        // Mission config
        $mc = self::wpmlEl($doc, $document, 'missionConfig');
        self::wpmlEl($doc, $mc, 'flyToWaylineMode', 'safely');
        self::wpmlEl($doc, $mc, 'finishAction', 'goHome');
        self::wpmlEl($doc, $mc, 'exitOnRCLost', 'executeLostAction');
        self::wpmlEl($doc, $mc, 'executeRCLostAction', 'goBack');
        self::wpmlEl($doc, $mc, 'globalTransitionalSpeed', '5.0');

        $droneInfo = self::wpmlEl($doc, $mc, 'droneInfo');
        self::wpmlEl($doc, $droneInfo, 'droneEnumValue', (string) $profile['droneEnumValue']);
        self::wpmlEl($doc, $droneInfo, 'droneSubEnumValue', (string) $profile['droneSubEnumValue']);

        $payloadInfo = self::wpmlEl($doc, $mc, 'payloadInfo');
        self::wpmlEl($doc, $payloadInfo, 'payloadEnumValue', (string) $profile['payloadEnumValue']);
        self::wpmlEl($doc, $payloadInfo, 'payloadSubEnumValue', '0');
        self::wpmlEl($doc, $payloadInfo, 'payloadPositionIndex', '0');

        // Folder with placemarks
        $folder = self::kmlEl($doc, $document, 'Folder');
        self::wpmlEl($doc, $folder, 'templateType', 'waypoint');
        self::wpmlEl($doc, $folder, 'templateId', '0');
        self::wpmlEl($doc, $folder, 'autoFlightSpeed', '5.0');

        $coordSys = self::wpmlEl($doc, $folder, 'waylineCoordinateSysParam');
        self::wpmlEl($doc, $coordSys, 'coordinateMode', 'WGS84');
        self::wpmlEl($doc, $coordSys, 'heightMode', 'relativeToStartPoint');

        foreach ($waypoints as $wp) {
            $pm = self::kmlEl($doc, $folder, 'Placemark');
            $point = self::kmlEl($doc, $pm, 'Point');
            $coords = self::kmlEl($doc, $point, 'coordinates',
                sprintf('%.7f,%.7f', $wp['lng'], $wp['lat']));

            self::wpmlEl($doc, $pm, 'index', (string) ($wp['index'] ?? 0));
            self::wpmlEl($doc, $pm, 'executeHeight', sprintf('%.1f', $wp['altitude_m'] ?? 30));
            self::wpmlEl($doc, $pm, 'waypointSpeed', sprintf('%.1f', $wp['speed_ms'] ?? 5));

            $hp = self::wpmlEl($doc, $pm, 'waypointHeadingParam');
            if (isset($wp['heading_deg']) && $wp['heading_deg'] !== null) {
                self::wpmlEl($doc, $hp, 'waypointHeadingMode', 'smoothTransition');
                self::wpmlEl($doc, $hp, 'waypointHeadingAngle', sprintf('%.1f', $wp['heading_deg']));
            } else {
                self::wpmlEl($doc, $hp, 'waypointHeadingMode', 'followWayline');
            }

            $tp = self::wpmlEl($doc, $pm, 'waypointTurnParam');
            self::wpmlEl($doc, $tp, 'waypointTurnMode', $wp['turn_mode'] ?? 'toPointAndStopWithDiscontinuityCurvature');
            self::wpmlEl($doc, $tp, 'waypointTurnDampingDist', sprintf('%.1f', $wp['turn_damping_dist'] ?? 0));
        }

        return $doc->saveXML();
    }

    private static function buildWaylinesWpml(array $waypoints, array $profile): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $kml = $doc->createElementNS(self::KML_NS, 'kml');
        $kml->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:wpml', self::WPML_NS);
        $doc->appendChild($kml);

        $document = self::kmlEl($doc, $kml, 'Document');

        $mc = self::wpmlEl($doc, $document, 'missionConfig');
        self::wpmlEl($doc, $mc, 'flyToWaylineMode', 'safely');
        self::wpmlEl($doc, $mc, 'finishAction', 'goHome');
        self::wpmlEl($doc, $mc, 'exitOnRCLost', 'executeLostAction');
        self::wpmlEl($doc, $mc, 'executeRCLostAction', 'goBack');
        self::wpmlEl($doc, $mc, 'globalTransitionalSpeed', '5.0');

        $droneInfo = self::wpmlEl($doc, $mc, 'droneInfo');
        self::wpmlEl($doc, $droneInfo, 'droneEnumValue', (string) $profile['droneEnumValue']);
        self::wpmlEl($doc, $droneInfo, 'droneSubEnumValue', (string) $profile['droneSubEnumValue']);

        $folder = self::kmlEl($doc, $document, 'Folder');
        self::wpmlEl($doc, $folder, 'templateId', '0');
        self::wpmlEl($doc, $folder, 'waylineId', '0');
        self::wpmlEl($doc, $folder, 'distance', '0');
        self::wpmlEl($doc, $folder, 'duration', '0');
        self::wpmlEl($doc, $folder, 'autoFlightSpeed', '5.0');

        $actionGroupId = 0;

        foreach ($waypoints as $wp) {
            $pm = self::kmlEl($doc, $folder, 'Placemark');
            $point = self::kmlEl($doc, $pm, 'Point');
            self::kmlEl($doc, $point, 'coordinates',
                sprintf('%.7f,%.7f', $wp['lng'], $wp['lat']));

            $idx = $wp['index'] ?? 0;
            self::wpmlEl($doc, $pm, 'index', (string) $idx);
            self::wpmlEl($doc, $pm, 'executeHeight', sprintf('%.1f', $wp['altitude_m'] ?? 30));
            self::wpmlEl($doc, $pm, 'waypointSpeed', sprintf('%.1f', $wp['speed_ms'] ?? 5));

            $hp = self::wpmlEl($doc, $pm, 'waypointHeadingParam');
            if (isset($wp['heading_deg']) && $wp['heading_deg'] !== null) {
                self::wpmlEl($doc, $hp, 'waypointHeadingMode', 'smoothTransition');
                self::wpmlEl($doc, $hp, 'waypointHeadingAngle', sprintf('%.1f', $wp['heading_deg']));
            } else {
                self::wpmlEl($doc, $hp, 'waypointHeadingMode', 'followWayline');
            }

            $tp = self::wpmlEl($doc, $pm, 'waypointTurnParam');
            self::wpmlEl($doc, $tp, 'waypointTurnMode', $wp['turn_mode'] ?? 'toPointAndStopWithDiscontinuityCurvature');
            self::wpmlEl($doc, $tp, 'waypointTurnDampingDist', sprintf('%.1f', $wp['turn_damping_dist'] ?? 0));

            // Action group
            $ag = self::wpmlEl($doc, $pm, 'actionGroup');
            self::wpmlEl($doc, $ag, 'actionGroupId', (string) $actionGroupId);
            self::wpmlEl($doc, $ag, 'actionGroupStartIndex', (string) $idx);
            self::wpmlEl($doc, $ag, 'actionGroupEndIndex', (string) $idx);
            self::wpmlEl($doc, $ag, 'actionGroupMode', 'sequence');
            $trigger = self::wpmlEl($doc, $ag, 'actionTrigger');
            self::wpmlEl($doc, $trigger, 'actionTriggerType', 'reachPoint');

            $actionIdx = 0;

            // Gimbal rotate
            $gimbalAction = self::wpmlEl($doc, $ag, 'action');
            self::wpmlEl($doc, $gimbalAction, 'actionId', (string) $actionIdx);
            self::wpmlEl($doc, $gimbalAction, 'actionActuatorFunc', 'gimbalRotate');
            $gp = self::wpmlEl($doc, $gimbalAction, 'actionActuatorFuncParam');
            self::wpmlEl($doc, $gp, 'gimbalRotateMode', 'absoluteAngle');
            self::wpmlEl($doc, $gp, 'gimbalPitchRotateEnable', '1');
            self::wpmlEl($doc, $gp, 'gimbalPitchRotateAngle', sprintf('%.1f', $wp['gimbal_pitch_deg'] ?? -90));
            self::wpmlEl($doc, $gp, 'gimbalRollRotateEnable', '0');
            self::wpmlEl($doc, $gp, 'gimbalRollRotateAngle', '0.0');
            self::wpmlEl($doc, $gp, 'gimbalYawRotateEnable', '0');
            self::wpmlEl($doc, $gp, 'gimbalYawRotateAngle', '0.0');
            self::wpmlEl($doc, $gp, 'gimbalRotateTimeEnable', '0');
            self::wpmlEl($doc, $gp, 'gimbalRotateTime', '0');
            self::wpmlEl($doc, $gp, 'payloadPositionIndex', '0');
            $actionIdx++;

            // Hover
            $hoverTime = $wp['hover_time_s'] ?? 0;
            if ($hoverTime > 0) {
                $hoverAction = self::wpmlEl($doc, $ag, 'action');
                self::wpmlEl($doc, $hoverAction, 'actionId', (string) $actionIdx);
                self::wpmlEl($doc, $hoverAction, 'actionActuatorFunc', 'hover');
                $hp2 = self::wpmlEl($doc, $hoverAction, 'actionActuatorFuncParam');
                self::wpmlEl($doc, $hp2, 'hoverTime', sprintf('%.1f', $hoverTime));
                $actionIdx++;
            }

            // Camera action
            $actionType = $wp['action_type'] ?? null;
            if ($actionType) {
                $camAction = self::wpmlEl($doc, $ag, 'action');
                self::wpmlEl($doc, $camAction, 'actionId', (string) $actionIdx);
                self::wpmlEl($doc, $camAction, 'actionActuatorFunc', $actionType);
                $cp = self::wpmlEl($doc, $camAction, 'actionActuatorFuncParam');
                self::wpmlEl($doc, $cp, 'payloadPositionIndex', '0');
                if ($actionType === 'takePhoto') {
                    self::wpmlEl($doc, $cp, 'fileSuffix', 'photo');
                    self::wpmlEl($doc, $cp, 'payloadLensIndex', 'wide');
                }
            }

            $actionGroupId++;
        }

        return $doc->saveXML();
    }

    // Helper: create KML-namespaced element
    private static function kmlEl(\DOMDocument $doc, \DOMElement $parent, string $tag, ?string $text = null): \DOMElement
    {
        $el = $doc->createElementNS(self::KML_NS, $tag);
        if ($text !== null) {
            $el->appendChild($doc->createTextNode($text));
        }
        $parent->appendChild($el);
        return $el;
    }

    // Helper: create WPML-namespaced element
    private static function wpmlEl(\DOMDocument $doc, \DOMElement $parent, string $tag, ?string $text = null): \DOMElement
    {
        $el = $doc->createElementNS(self::WPML_NS, 'wpml:' . $tag);
        if ($text !== null) {
            $el->appendChild($doc->createTextNode($text));
        }
        $parent->appendChild($el);
        return $el;
    }
}
