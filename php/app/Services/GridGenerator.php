<?php

namespace App\Services;

/**
 * Grid/area mapping generator.
 * Generates parallel flight lines (lawn-mower pattern) from a polygon.
 * Ported from services/grid_generator.py.
 */
class GridGenerator
{
    public static function generateGrid(array $polygonCoords, array $config): array
    {
        if (count($polygonCoords) < 3) {
            return [];
        }

        $spacingM    = $config['spacing_m'] ?? 20;
        $angleDeg    = $config['angle_deg'] ?? 0;
        $altitudeM   = $config['altitude_m'] ?? 30;
        $speedMs     = $config['speed_ms'] ?? 5;
        $pattern     = $config['pattern'] ?? 'parallel';
        $gimbalPitch = $config['gimbal_pitch_deg'] ?? -90;
        $actionType  = $config['action_type'] ?? 'takePhoto';

        $centerLat = array_sum(array_column($polygonCoords, 0)) / count($polygonCoords);
        $centerLng = array_sum(array_column($polygonCoords, 1)) / count($polygonCoords);

        $polyM = [];
        foreach ($polygonCoords as $c) {
            $polyM[] = GeoUtils::toMetres($c[0], $c[1], $centerLat, $centerLng);
        }

        $waypoints = self::generateScanLines(
            $polyM, $spacingM, $angleDeg, $altitudeM,
            $speedMs, $gimbalPitch, $actionType, $centerLat, $centerLng
        );

        if ($pattern === 'crosshatch') {
            $crossWps = self::generateScanLines(
                $polyM, $spacingM, $angleDeg + 90, $altitudeM,
                $speedMs, $gimbalPitch, $actionType, $centerLat, $centerLng
            );
            $offset = count($waypoints);
            foreach ($crossWps as &$w) {
                $w['index'] += $offset;
            }
            $waypoints = array_merge($waypoints, $crossWps);
        }

        return $waypoints;
    }

    private static function generateScanLines(
        array $polyM, float $spacingM, float $angleDeg, float $altitudeM,
        float $speedMs, float $gimbalPitch, string $actionType,
        float $centerLat, float $centerLng
    ): array {
        $angleRad = deg2rad($angleDeg);

        $rotated = [];
        foreach ($polyM as [$x, $y]) {
            $rotated[] = GeoUtils::rotate($x, $y, -$angleRad);
        }

        $xs = array_column($rotated, 0);
        $ys = array_column($rotated, 1);
        $minX = min($xs);
        $maxX = max($xs);

        $waypoints = [];
        $lineIdx = 0;
        $x = $minX + $spacingM / 2;

        while ($x < $maxX) {
            $intersections = self::linePolygonIntersections($x, $rotated);

            if (count($intersections) >= 2) {
                sort($intersections);
                for ($i = 0; $i < count($intersections) - 1; $i += 2) {
                    $yStart = $intersections[$i];
                    $yEnd = $intersections[$i + 1];

                    if ($lineIdx % 2 === 1) {
                        [$yStart, $yEnd] = [$yEnd, $yStart];
                    }

                    [$sx, $sy] = GeoUtils::rotate($x, $yStart, $angleRad);
                    [$ex, $ey] = GeoUtils::rotate($x, $yEnd, $angleRad);

                    [$startLat, $startLng] = GeoUtils::toLatLng($sx, $sy, $centerLat, $centerLng);
                    [$endLat, $endLng] = GeoUtils::toLatLng($ex, $ey, $centerLat, $centerLng);

                    $wp = [
                        'index' => count($waypoints), 'lat' => $startLat, 'lng' => $startLng,
                        'altitude_m' => $altitudeM, 'speed_ms' => $speedMs, 'heading_deg' => null,
                        'gimbal_pitch_deg' => $gimbalPitch,
                        'turn_mode' => 'toPointAndPassWithContinuityCurvature',
                        'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                        'action_type' => $actionType, 'poi_lat' => null, 'poi_lng' => null,
                    ];
                    $waypoints[] = $wp;

                    $wp['index'] = count($waypoints);
                    $wp['lat'] = $endLat;
                    $wp['lng'] = $endLng;
                    $waypoints[] = $wp;
                }
                $lineIdx++;
            }
            $x += $spacingM;
        }

        return $waypoints;
    }

    private static function linePolygonIntersections(float $x, array $polygon): array
    {
        $intersections = [];
        $n = count($polygon);
        for ($i = 0; $i < $n; $i++) {
            [$x1, $y1] = $polygon[$i];
            [$x2, $y2] = $polygon[($i + 1) % $n];

            if ($x1 == $x2) continue;

            if (($x1 <= $x && $x <= $x2) || ($x2 <= $x && $x <= $x1)) {
                $t = ($x - $x1) / ($x2 - $x1);
                $intersections[] = $y1 + $t * ($y2 - $y1);
            }
        }
        return $intersections;
    }
}
