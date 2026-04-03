<?php

namespace App\Services;

/**
 * Pre-built mission pattern generators: orbit, spiral, cable cam.
 * Ported from services/mission_patterns.py.
 */
class MissionPatterns
{
    public static function generateOrbit(
        float $centerLat, float $centerLng,
        float $radiusM = 30, float $altitudeM = 30,
        int $numPoints = 12, float $speedMs = 5,
        string $direction = 'cw', float $gimbalPitch = -45,
        string $actionType = 'takePhoto'
    ): array {
        $waypoints = [];
        for ($i = 0; $i < $numPoints; $i++) {
            $angle = ($direction === 'cw')
                ? (2 * M_PI * $i) / $numPoints
                : -(2 * M_PI * $i) / $numPoints;

            [$lat, $lng] = GeoUtils::offsetPoint($centerLat, $centerLng, $radiusM, $angle);
            $heading = GeoUtils::headingTo($lat, $lng, $centerLat, $centerLng);

            $waypoints[] = [
                'index' => $i, 'lat' => $lat, 'lng' => $lng,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'heading_deg' => $heading, 'gimbal_pitch_deg' => $gimbalPitch,
                'turn_mode' => 'toPointAndPassWithContinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                'action_type' => $actionType,
                'poi_lat' => $centerLat, 'poi_lng' => $centerLng,
            ];
        }
        return $waypoints;
    }

    public static function generateSpiral(
        float $centerLat, float $centerLng,
        float $radiusM = 30, float $startAltitudeM = 20, float $endAltitudeM = 60,
        int $numRevolutions = 3, int $pointsPerRev = 12,
        float $speedMs = 4, string $direction = 'cw',
        float $gimbalPitch = -45, string $actionType = 'takePhoto'
    ): array {
        $totalPoints = $numRevolutions * $pointsPerRev;
        $altStep = ($endAltitudeM - $startAltitudeM) / max($totalPoints - 1, 1);

        $waypoints = [];
        for ($i = 0; $i < $totalPoints; $i++) {
            $angle = ($direction === 'cw')
                ? (2 * M_PI * $i) / $pointsPerRev
                : -(2 * M_PI * $i) / $pointsPerRev;

            $alt = $startAltitudeM + $altStep * $i;
            [$lat, $lng] = GeoUtils::offsetPoint($centerLat, $centerLng, $radiusM, $angle);
            $heading = GeoUtils::headingTo($lat, $lng, $centerLat, $centerLng);

            $waypoints[] = [
                'index' => $i, 'lat' => $lat, 'lng' => $lng,
                'altitude_m' => round($alt, 1), 'speed_ms' => $speedMs,
                'heading_deg' => $heading, 'gimbal_pitch_deg' => $gimbalPitch,
                'turn_mode' => 'toPointAndPassWithContinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                'action_type' => $actionType,
                'poi_lat' => $centerLat, 'poi_lng' => $centerLng,
            ];
        }
        return $waypoints;
    }

    public static function generateCableCam(
        float $startLat, float $startLng, float $endLat, float $endLng,
        float $altitudeM = 30, int $numPoints = 10, float $speedMs = 3,
        float $gimbalPitch = -30, string $actionType = 'startRecord'
    ): array {
        $waypoints = [];
        $heading = GeoUtils::headingTo($startLat, $startLng, $endLat, $endLng);

        for ($i = 0; $i < $numPoints; $i++) {
            $t = $i / max($numPoints - 1, 1);
            $lat = $startLat + $t * ($endLat - $startLat);
            $lng = $startLng + $t * ($endLng - $startLng);

            $wpAction = null;
            if ($i === 0) {
                $wpAction = $actionType;
            } elseif ($i === $numPoints - 1 && $actionType === 'startRecord') {
                $wpAction = 'stopRecord';
            }

            $waypoints[] = [
                'index' => $i, 'lat' => $lat, 'lng' => $lng,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'heading_deg' => $heading, 'gimbal_pitch_deg' => $gimbalPitch,
                'turn_mode' => 'toPointAndPassWithContinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                'action_type' => $wpAction,
                'poi_lat' => null, 'poi_lng' => null,
            ];
        }
        return $waypoints;
    }
}
