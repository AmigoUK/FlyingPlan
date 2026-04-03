<?php

namespace App\Services;

/**
 * Facade scanning and structure inspection pattern generators.
 * Ported from services/facade_scanner.py.
 */
class FacadeScanner
{
    public static function generateFacadeScan(array $faceStart, array $faceEnd, array $config): array
    {
        $standoff   = $config['standoff_m'] ?? 10;
        $colSpacing = $config['column_spacing_m'] ?? 5;
        $minAlt     = $config['min_altitude_m'] ?? 10;
        $maxAlt     = $config['max_altitude_m'] ?? 40;
        $altStep    = $config['altitude_step_m'] ?? 5;
        $speedMs    = $config['speed_ms'] ?? 3;
        $actionType = $config['action_type'] ?? 'takePhoto';

        $wallBearingDeg = GeoUtils::headingTo($faceStart[0], $faceStart[1], $faceEnd[0], $faceEnd[1]);
        $wallBearingRad = deg2rad($wallBearingDeg);
        $perpBearingRad = $wallBearingRad - M_PI / 2;

        $wallLength = GeoUtils::haversine($faceStart[0], $faceStart[1], $faceEnd[0], $faceEnd[1]);
        $numColumns = max(1, (int) ($wallLength / $colSpacing) + 1);

        $altitudes = [];
        $alt = $minAlt;
        while ($alt <= $maxAlt) {
            $altitudes[] = $alt;
            $alt += $altStep;
        }
        if (empty($altitudes)) {
            $altitudes = [$minAlt];
        }

        $waypoints = [];
        for ($col = 0; $col < $numColumns; $col++) {
            $t = $col / max($numColumns - 1, 1);
            $wallLat = $faceStart[0] + $t * ($faceEnd[0] - $faceStart[0]);
            $wallLng = $faceStart[1] + $t * ($faceEnd[1] - $faceStart[1]);

            [$droneLat, $droneLng] = GeoUtils::offsetPoint($wallLat, $wallLng, $standoff, $perpBearingRad);

            $colAlts = ($col % 2 === 0) ? $altitudes : array_reverse($altitudes);

            foreach ($colAlts as $altM) {
                $relHeight = $altM - ($minAlt + $maxAlt) / 2;
                $gimbal = min(0, -rad2deg(atan2($relHeight, $standoff)));
                $gimbal = max(-90, round($gimbal, 1));

                $camHeading = GeoUtils::headingTo($droneLat, $droneLng, $wallLat, $wallLng);

                $waypoints[] = [
                    'index' => count($waypoints), 'lat' => $droneLat, 'lng' => $droneLng,
                    'altitude_m' => $altM, 'speed_ms' => $speedMs,
                    'heading_deg' => $camHeading, 'gimbal_pitch_deg' => $gimbal,
                    'turn_mode' => 'toPointAndStopWithDiscontinuityCurvature',
                    'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                    'action_type' => $actionType,
                    'poi_lat' => $wallLat, 'poi_lng' => $wallLng,
                ];
            }
        }

        return $waypoints;
    }

    public static function generateMultiFaceScan(array $buildingPolygon, array $config): array
    {
        if (count($buildingPolygon) < 2) {
            return [];
        }

        $allWps = [];
        $n = count($buildingPolygon);

        for ($i = 0; $i < $n; $i++) {
            $start = $buildingPolygon[$i];
            $end   = $buildingPolygon[($i + 1) % $n];
            $faceWps = self::generateFacadeScan($start, $end, $config);
            $offset = count($allWps);
            foreach ($faceWps as &$w) {
                $w['index'] += $offset;
            }
            $allWps = array_merge($allWps, $faceWps);
        }

        return $allWps;
    }

    public static function generateMultiAltitudeOrbit(
        float $centerLat, float $centerLng, array $config
    ): array {
        $radius    = $config['radius_m'] ?? 30;
        $minAlt    = $config['min_altitude_m'] ?? 15;
        $maxAlt    = $config['max_altitude_m'] ?? 60;
        $altStep   = $config['altitude_step_m'] ?? 15;
        $numPoints = (int) ($config['num_points'] ?? 12);
        $speedMs   = $config['speed_ms'] ?? 5;
        $direction = $config['direction'] ?? 'cw';
        $actionType = $config['action_type'] ?? 'takePhoto';

        $waypoints = [];
        $alt = $minAlt;
        while ($alt <= $maxAlt) {
            $gimbalPitch = -rad2deg(atan2($alt, $radius));
            $gimbalPitch = max(-90, round($gimbalPitch, 1));

            for ($i = 0; $i < $numPoints; $i++) {
                $angle = ($direction === 'cw')
                    ? (2 * M_PI * $i) / $numPoints
                    : -(2 * M_PI * $i) / $numPoints;

                [$lat, $lng] = GeoUtils::offsetPoint($centerLat, $centerLng, $radius, $angle);
                $hdg = GeoUtils::headingTo($lat, $lng, $centerLat, $centerLng);

                $waypoints[] = [
                    'index' => count($waypoints), 'lat' => $lat, 'lng' => $lng,
                    'altitude_m' => round($alt, 1), 'speed_ms' => $speedMs,
                    'heading_deg' => $hdg, 'gimbal_pitch_deg' => $gimbalPitch,
                    'turn_mode' => 'toPointAndPassWithContinuityCurvature',
                    'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                    'action_type' => $actionType,
                    'poi_lat' => $centerLat, 'poi_lng' => $centerLng,
                ];
            }
            $alt += $altStep;
        }

        return $waypoints;
    }
}
