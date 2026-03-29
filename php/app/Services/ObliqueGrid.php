<?php

namespace App\Services;

/**
 * Oblique grid and double-grid mission planner for 3D photogrammetry.
 * Ported from services/oblique_grid.py.
 */
class ObliqueGrid
{
    public static function generateObliqueGrid(array $polygonCoords, array $config): array
    {
        if (count($polygonCoords) < 3) {
            return [];
        }

        $mode         = $config['capture_mode'] ?? 'nadir';
        $spacingM     = $config['spacing_m'] ?? 20;
        $angleDeg     = $config['angle_deg'] ?? 0;
        $altitudeM    = $config['altitude_m'] ?? 50;
        $speedMs      = $config['speed_ms'] ?? 5;
        $obliquePitch = $config['gimbal_pitch_deg'] ?? -45;
        $headingMode  = $config['heading_mode'] ?? 'along_track';
        $fixedHeading = $config['fixed_heading_deg'] ?? 0;
        $actionType   = $config['action_type'] ?? 'takePhoto';

        if ($mode === 'nadir') {
            return GridGenerator::generateGrid($polygonCoords, [
                'spacing_m' => $spacingM, 'angle_deg' => $angleDeg,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'gimbal_pitch_deg' => -90, 'action_type' => $actionType,
            ]);
        }

        if ($mode === 'oblique') {
            $wps = GridGenerator::generateGrid($polygonCoords, [
                'spacing_m' => $spacingM, 'angle_deg' => $angleDeg,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'gimbal_pitch_deg' => $obliquePitch, 'action_type' => $actionType,
            ]);
            self::applyHeadings($wps, $headingMode, $fixedHeading);
            return $wps;
        }

        if ($mode === 'double_grid') {
            $nadirWps = GridGenerator::generateGrid($polygonCoords, [
                'spacing_m' => $spacingM, 'angle_deg' => $angleDeg,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'gimbal_pitch_deg' => -90, 'action_type' => $actionType,
            ]);

            $obliqueWps = GridGenerator::generateGrid($polygonCoords, [
                'spacing_m' => $spacingM, 'angle_deg' => fmod($angleDeg + 90, 360),
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'gimbal_pitch_deg' => $obliquePitch, 'action_type' => $actionType,
            ]);

            self::applyHeadings($obliqueWps, $headingMode, $fixedHeading);

            $offset = count($nadirWps);
            foreach ($obliqueWps as &$w) {
                $w['index'] += $offset;
            }
            return array_merge($nadirWps, $obliqueWps);
        }

        if ($mode === 'multi_angle') {
            $allWps = GridGenerator::generateGrid($polygonCoords, [
                'spacing_m' => $spacingM, 'angle_deg' => $angleDeg,
                'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                'gimbal_pitch_deg' => -90, 'action_type' => $actionType,
            ]);

            foreach ([0, 90, 180, 270] as $passAngle) {
                $gridAngle = fmod($angleDeg + $passAngle, 360);
                $passWps = GridGenerator::generateGrid($polygonCoords, [
                    'spacing_m' => $spacingM, 'angle_deg' => $gridAngle,
                    'altitude_m' => $altitudeM, 'speed_ms' => $speedMs,
                    'gimbal_pitch_deg' => $obliquePitch, 'action_type' => $actionType,
                ]);
                foreach ($passWps as &$wp) {
                    $wp['heading_deg'] = $gridAngle;
                }
                $offset = count($allWps);
                foreach ($passWps as &$wp) {
                    $wp['index'] += $offset;
                }
                $allWps = array_merge($allWps, $passWps);
            }
            return $allWps;
        }

        return [];
    }

    private static function applyHeadings(array &$waypoints, string $headingMode, float $fixedHeading = 0): void
    {
        if ($headingMode === 'fixed') {
            foreach ($waypoints as &$wp) {
                $wp['heading_deg'] = $fixedHeading;
            }
        } elseif ($headingMode === 'along_track') {
            $n = count($waypoints);
            for ($i = 0; $i < $n; $i++) {
                if ($i < $n - 1) {
                    $waypoints[$i]['heading_deg'] = GeoUtils::headingTo(
                        $waypoints[$i]['lat'], $waypoints[$i]['lng'],
                        $waypoints[$i + 1]['lat'], $waypoints[$i + 1]['lng']
                    );
                } elseif ($i > 0) {
                    $waypoints[$i]['heading_deg'] = $waypoints[$i - 1]['heading_deg'];
                }
            }
        }
    }
}
