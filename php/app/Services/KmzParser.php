<?php

namespace App\Services;

/**
 * KMZ parser for importing DJI waypoint mission files.
 * Extracts waypoints from wpmz/template.kml and/or wpmz/waylines.wpml.
 * Auto-detects drone model from drone profiles.
 *
 * Ported from services/kmz_parser.py.
 */
class KmzParser
{
    private const WPML_NS = 'http://www.dji.com/wpmz/1.0.6';
    private const KML_NS  = 'http://www.opengis.net/kml/2.2';

    /**
     * Parse a KMZ file and return waypoints + detected drone model.
     *
     * @param string $fileContent Binary KMZ content
     * @return array{waypoints: array, drone_model: ?string, error: ?string}
     */
    public static function parseKmz(string $fileContent): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'kmz_parse_');
        file_put_contents($tmpFile, $fileContent);

        $zip = new \ZipArchive();
        if ($zip->open($tmpFile) !== true) {
            unlink($tmpFile);
            return ['waypoints' => [], 'drone_model' => null, 'error' => 'Not a valid KMZ/ZIP file'];
        }

        try {
            // Try waylines.wpml first (more detail), fall back to template.kml
            $wpmlContent = $zip->getFromName('wpmz/waylines.wpml');
            if ($wpmlContent !== false) {
                $zip->close();
                unlink($tmpFile);
                return self::parseWpml($wpmlContent);
            }

            $kmlContent = $zip->getFromName('wpmz/template.kml');
            if ($kmlContent !== false) {
                $zip->close();
                unlink($tmpFile);
                return self::parseTemplateKml($kmlContent);
            }

            $zip->close();
            unlink($tmpFile);
            return ['waypoints' => [], 'drone_model' => null, 'error' => 'No wpmz/template.kml or wpmz/waylines.wpml found'];
        } catch (\Exception $e) {
            $zip->close();
            unlink($tmpFile);
            return ['waypoints' => [], 'drone_model' => null, 'error' => $e->getMessage()];
        }
    }

    private static function parseWpml(string $xmlContent): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xmlContent);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('kml', self::KML_NS);
        $xpath->registerNamespace('wpml', self::WPML_NS);

        $droneModel = self::detectDrone($xpath);
        $waypoints = [];

        $placemarks = $xpath->query('//kml:Placemark');
        foreach ($placemarks as $pm) {
            $wp = self::extractWaypoint($pm, $xpath);
            if ($wp === null) continue;

            // Extract action type from action groups
            $actionGroups = $xpath->query('wpml:actionGroup', $pm);
            foreach ($actionGroups as $ag) {
                $actions = $xpath->query('wpml:action', $ag);
                foreach ($actions as $action) {
                    $func = self::xpathText($xpath, 'wpml:actionActuatorFunc', $action);
                    if ($func && !in_array($func, ['gimbalRotate', 'hover'], true)) {
                        $wp['action_type'] = $func;
                        break;
                    }
                    if ($func === 'hover') {
                        $params = $xpath->query('wpml:actionActuatorFuncParam', $action)->item(0);
                        if ($params) {
                            $ht = self::xpathText($xpath, 'wpml:hoverTime', $params);
                            if ($ht) {
                                $wp['hover_time_s'] = (float) $ht;
                            }
                        }
                    }
                }
            }

            $waypoints[] = $wp;
        }

        return ['waypoints' => $waypoints, 'drone_model' => $droneModel, 'error' => null];
    }

    private static function parseTemplateKml(string $xmlContent): array
    {
        $doc = new \DOMDocument();
        $doc->loadXML($xmlContent);
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('kml', self::KML_NS);
        $xpath->registerNamespace('wpml', self::WPML_NS);

        $droneModel = self::detectDrone($xpath);
        $waypoints = [];

        $placemarks = $xpath->query('//kml:Placemark');
        foreach ($placemarks as $pm) {
            $wp = self::extractWaypoint($pm, $xpath);
            if ($wp !== null) {
                $waypoints[] = $wp;
            }
        }

        return ['waypoints' => $waypoints, 'drone_model' => $droneModel, 'error' => null];
    }

    private static function extractWaypoint(\DOMElement $placemark, \DOMXPath $xpath): ?array
    {
        $coordsNode = $xpath->query('.//kml:coordinates', $placemark)->item(0);
        if (!$coordsNode || !trim($coordsNode->textContent)) {
            return null;
        }

        $parts = explode(',', trim($coordsNode->textContent));
        if (count($parts) < 2) {
            return null;
        }

        $lng = (float) $parts[0];
        $lat = (float) $parts[1];

        $indexText = self::xpathText($xpath, 'wpml:index', $placemark);
        $index = $indexText !== null ? (int) $indexText : 0;

        $altitude = self::xpathFloat($xpath, 'wpml:executeHeight', $placemark, 30.0);
        $speed = self::xpathFloat($xpath, 'wpml:waypointSpeed', $placemark, 5.0);

        // Heading
        $heading = null;
        $hp = $xpath->query('wpml:waypointHeadingParam', $placemark)->item(0);
        if ($hp) {
            $mode = self::xpathText($xpath, 'wpml:waypointHeadingMode', $hp);
            if ($mode === 'smoothTransition') {
                $heading = self::xpathFloat($xpath, 'wpml:waypointHeadingAngle', $hp, null);
            }
        }

        // Turn mode
        $turnMode = 'toPointAndStopWithDiscontinuityCurvature';
        $tp = $xpath->query('wpml:waypointTurnParam', $placemark)->item(0);
        if ($tp) {
            $tm = self::xpathText($xpath, 'wpml:waypointTurnMode', $tp);
            if ($tm) {
                $turnMode = $tm;
            }
        }

        return [
            'index'            => $index,
            'lat'              => $lat,
            'lng'              => $lng,
            'altitude_m'       => $altitude,
            'speed_ms'         => $speed,
            'heading_deg'      => $heading,
            'gimbal_pitch_deg' => -90.0,
            'turn_mode'        => $turnMode,
            'turn_damping_dist' => 0.0,
            'hover_time_s'     => 0.0,
            'action_type'      => null,
            'poi_lat'          => null,
            'poi_lng'          => null,
        ];
    }

    private static function detectDrone(\DOMXPath $xpath): ?string
    {
        $enumNode = $xpath->query('//wpml:droneEnumValue')->item(0);
        if (!$enumNode) {
            return null;
        }

        $enumVal = (int) $enumNode->textContent;
        $subNode = $xpath->query('//wpml:droneSubEnumValue')->item(0);
        $subVal = $subNode ? (int) $subNode->textContent : 0;

        // Exact match
        foreach (DroneProfiles::PROFILES as $key => $profile) {
            if ($profile['droneEnumValue'] === $enumVal && $profile['droneSubEnumValue'] === $subVal) {
                return $key;
            }
        }

        // Fallback: enum only
        foreach (DroneProfiles::PROFILES as $key => $profile) {
            if ($profile['droneEnumValue'] === $enumVal) {
                return $key;
            }
        }

        return null;
    }

    private static function xpathText(\DOMXPath $xpath, string $query, \DOMElement $context): ?string
    {
        $node = $xpath->query($query, $context)->item(0);
        return ($node && trim($node->textContent) !== '') ? trim($node->textContent) : null;
    }

    private static function xpathFloat(\DOMXPath $xpath, string $query, \DOMElement $context, ?float $default): ?float
    {
        $text = self::xpathText($xpath, $query, $context);
        if ($text !== null) {
            return (float) $text;
        }
        return $default;
    }
}
