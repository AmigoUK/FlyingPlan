<?php

namespace App\Services;

/**
 * Coverage analysis and overlap heatmap for photogrammetry missions.
 * Ported from services/coverage_analyzer.py.
 */
class CoverageAnalyzer
{
    public static function computePhotoFootprint(
        float $lat, float $lng, float $altitudeM,
        float $gimbalPitchDeg, float $headingDeg,
        string $droneModel = 'mini_4_pro'
    ): array {
        $profile = DroneProfiles::getProfile($droneModel);
        $sensorW = $profile['sensor_width_mm'];
        $sensorH = $profile['sensor_height_mm'];
        $focal   = $profile['focal_length_mm'];

        $headingRad = deg2rad($headingDeg ?: 0);

        if ($gimbalPitchDeg <= -89) {
            // Nadir: simple rectangle
            $halfW = $altitudeM * ($sensorW / 2) / $focal;
            $halfH = $altitudeM * ($sensorH / 2) / $focal;
            $cornersLocal = [
                [-$halfW, -$halfH], [$halfW, -$halfH],
                [$halfW, $halfH], [-$halfW, $halfH],
            ];
        } else {
            // Oblique: trapezoid
            $viewAngle = M_PI / 2 + deg2rad($gimbalPitchDeg);
            if ($viewAngle <= 0.05) {
                $viewAngle = 0.05;
            }

            $halfFovV = atan2($sensorH / 2, $focal);
            $halfFovH = atan2($sensorW / 2, $focal);

            $nearAngle = $viewAngle + $halfFovV;
            $farAngle  = $viewAngle - $halfFovV;

            $nearDist = ($nearAngle >= M_PI / 2) ? $altitudeM * 10 : $altitudeM * tan($nearAngle);
            if ($farAngle >= M_PI / 2) {
                $farDist = $altitudeM * 10;
            } elseif ($farAngle <= 0) {
                $farDist = $altitudeM * 0.5;
            } else {
                $farDist = $altitudeM * tan($farAngle);
            }

            $nearHalfW = $nearDist * tan($halfFovH);
            $farHalfW  = $farDist * tan($halfFovH);

            $cornersLocal = [
                [-$farHalfW, $farDist], [$farHalfW, $farDist],
                [$nearHalfW, $nearDist], [-$nearHalfW, $nearDist],
            ];
        }

        $footprint = [];
        foreach ($cornersLocal as [$x, $y]) {
            [$rx, $ry] = GeoUtils::rotate($x, $y, -$headingRad);
            [$fpLat, $fpLng] = GeoUtils::toLatLng($rx, $ry, $lat, $lng);
            $footprint[] = [$fpLat, $fpLng];
        }

        return $footprint;
    }

    public static function computeCoverageGrid(
        array $waypoints, string $droneModel = 'mini_4_pro',
        float $resolutionM = 5
    ): array {
        if (empty($waypoints)) {
            return ['grid' => [], 'bounds' => [], 'resolution_m' => $resolutionM,
                    'stats' => [], 'rows' => 0, 'cols' => 0];
        }

        $footprints = [];
        foreach ($waypoints as $wp) {
            $footprints[] = self::computePhotoFootprint(
                $wp['lat'], $wp['lng'],
                $wp['altitude_m'] ?? 30,
                $wp['gimbal_pitch_deg'] ?? -90,
                $wp['heading_deg'] ?? 0,
                $droneModel
            );
        }

        if (empty($footprints)) {
            return ['grid' => [], 'bounds' => [], 'resolution_m' => $resolutionM,
                    'stats' => [], 'rows' => 0, 'cols' => 0];
        }

        $allLats = $allLngs = [];
        foreach ($footprints as $fp) {
            foreach ($fp as $p) {
                $allLats[] = $p[0];
                $allLngs[] = $p[1];
            }
        }
        $minLat = min($allLats); $maxLat = max($allLats);
        $minLng = min($allLngs); $maxLng = max($allLngs);

        $centerLat = ($minLat + $maxLat) / 2;
        $centerLng = ($minLng + $maxLng) / 2;

        [$minX, $minY] = self::toM($minLat, $minLng, $centerLat, $centerLng);
        [$maxX, $maxY] = self::toM($maxLat, $maxLng, $centerLat, $centerLng);

        $cols = max(1, (int) (($maxX - $minX) / $resolutionM) + 1);
        $rows = max(1, (int) (($maxY - $minY) / $resolutionM) + 1);

        // Cap grid size for memory safety (especially on shared hosting)
        if ($rows * $cols > 100000) {
            $scale = sqrt($rows * $cols / 100000);
            $resolutionM *= $scale;
            $cols = max(1, (int) (($maxX - $minX) / $resolutionM) + 1);
            $rows = max(1, (int) (($maxY - $minY) / $resolutionM) + 1);
        }

        $grid = array_fill(0, $rows, array_fill(0, $cols, 0));

        foreach ($footprints as $fp) {
            $fpM = [];
            foreach ($fp as $p) {
                $fpM[] = self::toM($p[0], $p[1], $centerLat, $centerLng);
            }

            $fpYs = array_column($fpM, 1);
            $fpXs = array_column($fpM, 0);
            $fpMinR = max(0, (int) ((min($fpYs) - $minY) / $resolutionM));
            $fpMaxR = min($rows - 1, (int) ((max($fpYs) - $minY) / $resolutionM));
            $fpMinC = max(0, (int) ((min($fpXs) - $minX) / $resolutionM));
            $fpMaxC = min($cols - 1, (int) ((max($fpXs) - $minX) / $resolutionM));

            for ($r = $fpMinR; $r <= $fpMaxR; $r++) {
                for ($c = $fpMinC; $c <= $fpMaxC; $c++) {
                    $cx = $minX + ($c + 0.5) * $resolutionM;
                    $cy = $minY + ($r + 0.5) * $resolutionM;
                    if (self::pointInQuad($cx, $cy, $fpM)) {
                        $grid[$r][$c]++;
                    }
                }
            }
        }

        $nonzero = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($grid[$r][$c] > 0) {
                    $nonzero[] = $grid[$r][$c];
                }
            }
        }
        $coveredCells = count($nonzero);
        $sufficient = count(array_filter($nonzero, fn($v) => $v >= 3));

        $stats = [
            'min_overlap'      => $nonzero ? min($nonzero) : 0,
            'max_overlap'      => $nonzero ? max($nonzero) : 0,
            'avg_overlap'      => $nonzero ? round(array_sum($nonzero) / count($nonzero), 1) : 0,
            'coverage_area_sqm' => round($coveredCells * $resolutionM * $resolutionM, 1),
            'sufficient_pct'   => $coveredCells ? round(100 * $sufficient / $coveredCells, 1) : 0,
        ];

        return [
            'grid' => $grid,
            'bounds' => [
                'min_lat' => $minLat, 'max_lat' => $maxLat,
                'min_lng' => $minLng, 'max_lng' => $maxLng,
            ],
            'resolution_m' => $resolutionM,
            'stats' => $stats,
            'rows' => $rows,
            'cols' => $cols,
        ];
    }

    private static function toM(float $lat, float $lng, float $centerLat, float $centerLng): array
    {
        $x = ($lng - $centerLng) * cos(deg2rad($centerLat)) * 111320;
        $y = ($lat - $centerLat) * 110540;
        return [$x, $y];
    }

    private static function pointInQuad(float $x, float $y, array $quad): bool
    {
        $n = count($quad);
        $inside = false;
        $j = $n - 1;
        for ($i = 0; $i < $n; $i++) {
            [$xi, $yi] = $quad[$i];
            [$xj, $yj] = $quad[$j];
            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }
        return $inside;
    }
}
