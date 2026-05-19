<?php

namespace App\Services;

/**
 * Photogrammetry quality estimator.
 * Ported from services/photogrammetry_estimator.py.
 */
class PhotogrammetryEstimator
{
    public static function estimatePointDensity(array $coverageData, string $droneModel = 'mini_4_pro'): array
    {
        $stats = $coverageData['stats'] ?? [];
        $avgOverlap = $stats['avg_overlap'] ?? 0;

        if ($avgOverlap <= 0) {
            return ['estimated_points_per_sqm' => 0, 'quality_label' => 'none'];
        }

        $baseDensity = min(1000, $avgOverlap * $avgOverlap * 15);

        if ($baseDensity >= 500) $label = 'excellent';
        elseif ($baseDensity >= 200) $label = 'good';
        elseif ($baseDensity >= 50) $label = 'moderate';
        else $label = 'poor';

        return ['estimated_points_per_sqm' => round($baseDensity, 0), 'quality_label' => $label];
    }

    public static function computeConvergenceAngles(
        array $waypoints, string $droneModel = 'mini_4_pro', ?array $samplePoints = null
    ): array {
        if (count($waypoints) < 2) {
            return ['avg_angle' => 0, 'min_angle' => 0, 'max_angle' => 0,
                    'quality_label' => 'poor', 'sample_results' => []];
        }

        if ($samplePoints === null) {
            $samplePoints = [];
            $step = max(1, (int) (count($waypoints) / 10));
            for ($i = 0; $i < count($waypoints); $i += $step) {
                $samplePoints[] = [$waypoints[$i]['lat'], $waypoints[$i]['lng']];
            }
        }

        $results = [];
        foreach ($samplePoints as $sp) {
            [$spLat, $spLng] = $sp;

            $viewingWps = [];
            foreach ($waypoints as $wp) {
                $dist = GeoUtils::haversine($spLat, $spLng, $wp['lat'], $wp['lng']);
                if ($dist < 500) {
                    $viewingWps[] = [
                        'bearing'  => GeoUtils::headingTo($wp['lat'], $wp['lng'], $spLat, $spLng),
                        'distance' => $dist,
                        'altitude' => $wp['altitude_m'] ?? 30,
                    ];
                }
            }

            if (count($viewingWps) < 2) {
                $results[] = ['lat' => $spLat, 'lng' => $spLng, 'angle' => 0];
                continue;
            }

            $maxAngle = 0;
            $n = count($viewingWps);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $diff = abs($viewingWps[$i]['bearing'] - $viewingWps[$j]['bearing']);
                    if ($diff > 180) $diff = 360 - $diff;
                    $maxAngle = max($maxAngle, $diff);
                }
            }

            $results[] = ['lat' => $spLat, 'lng' => $spLng, 'angle' => round($maxAngle, 1)];
        }

        $angles = array_column($results, 'angle');
        $avgAngle = count($angles) ? array_sum($angles) / count($angles) : 0;

        if ($avgAngle >= 45) $label = 'excellent';
        elseif ($avgAngle >= 25) $label = 'good';
        elseif ($avgAngle >= 10) $label = 'moderate';
        else $label = 'poor';

        return [
            'avg_angle'      => round($avgAngle, 1),
            'min_angle'      => $angles ? min($angles) : 0,
            'max_angle'      => $angles ? max($angles) : 0,
            'quality_label'  => $label,
            'sample_results' => $results,
        ];
    }

    public static function generateQualityReport(
        array $waypoints, string $droneModel = 'mini_4_pro', ?array $polygon = null
    ): array {
        if (empty($waypoints)) {
            return [
                'overall_rating' => 'red', 'metrics' => [],
                'recommendations' => ['Add waypoints to generate a quality report.'],
            ];
        }

        $coverage = CoverageAnalyzer::computeCoverageGrid($waypoints, $droneModel);
        $stats = $coverage['stats'] ?? [];

        $density = self::estimatePointDensity($coverage, $droneModel);
        $convergence = self::computeConvergenceAngles($waypoints, $droneModel);

        $profile = DroneProfiles::getProfile($droneModel);
        $altitudes = array_map(fn($w) => $w['altitude_m'] ?? 30, $waypoints);
        $avgAlt = array_sum($altitudes) / count($altitudes);
        $gsdCm = ($avgAlt * $profile['sensor_width_mm'])
            / ($profile['focal_length_mm'] * $profile['image_width_px']) * 100;

        $pitches = array_map(fn($w) => $w['gimbal_pitch_deg'] ?? -90, $waypoints);
        $hasNadir = !empty(array_filter($pitches, fn($p) => $p <= -80));
        $hasOblique = !empty(array_filter($pitches, fn($p) => $p > -75 && $p < -10));

        if ($hasNadir && $hasOblique) $captureMode = 'double_grid';
        elseif ($hasOblique) $captureMode = 'oblique_only';
        else $captureMode = 'nadir_only';

        $metrics = [];
        $avgOverlap = $stats['avg_overlap'] ?? 0;
        $suffPct = $stats['sufficient_pct'] ?? 0;

        // Overlap
        $metrics['overlap'] = [
            'value' => $avgOverlap, 'label' => 'Image Overlap',
            'rating' => $avgOverlap >= 5 ? 'green' : ($avgOverlap >= 3 ? 'yellow' : 'red'),
        ];

        // Coverage
        $metrics['coverage'] = [
            'value' => $suffPct, 'label' => 'Coverage (% with 3+ images)',
            'rating' => $suffPct >= 90 ? 'green' : ($suffPct >= 70 ? 'yellow' : 'red'),
        ];

        // GSD
        $metrics['gsd'] = [
            'value' => round($gsdCm, 2), 'label' => 'GSD (cm/px)',
            'rating' => $gsdCm <= 2 ? 'green' : ($gsdCm <= 5 ? 'yellow' : 'red'),
        ];

        // Convergence
        $convRating = str_replace(
            ['excellent', 'good', 'moderate', 'poor'],
            ['green', 'green', 'yellow', 'red'],
            $convergence['quality_label']
        );
        $metrics['convergence'] = [
            'value' => $convergence['avg_angle'], 'label' => 'Convergence Angle (deg)',
            'rating' => $convRating,
        ];

        // Point density
        $densRating = str_replace(
            ['excellent', 'good', 'moderate', 'poor', 'none'],
            ['green', 'green', 'yellow', 'red', 'red'],
            $density['quality_label']
        );
        $metrics['point_density'] = [
            'value' => $density['estimated_points_per_sqm'], 'label' => 'Est. Point Density (pts/m²)',
            'rating' => $densRating,
        ];

        // Capture mode
        $modeRatings = ['nadir_only' => 'red', 'oblique_only' => 'yellow', 'double_grid' => 'green'];
        $metrics['capture_mode'] = [
            'value' => ucwords(str_replace('_', ' ', $captureMode)),
            'label' => 'Capture Mode',
            'rating' => $modeRatings[$captureMode] ?? 'yellow',
        ];

        $ratings = array_column($metrics, 'rating');
        if (in_array('red', $ratings)) $overall = 'red';
        elseif (in_array('yellow', $ratings)) $overall = 'yellow';
        else $overall = 'green';

        $recs = [];
        if ($captureMode === 'nadir_only') $recs[] = 'Add oblique passes (double-grid or multi-angle) for 3D reconstruction.';
        if ($avgOverlap < 3) $recs[] = 'Increase overlap by reducing grid spacing or lowering altitude.';
        if ($suffPct < 70) $recs[] = 'Expand coverage area or add more flight lines to reduce gaps.';
        if ($convergence['avg_angle'] < 20) $recs[] = 'Add perpendicular flight passes to improve convergence angles.';
        if ($gsdCm > 5) $recs[] = 'Lower flight altitude to improve ground resolution (GSD).';

        return [
            'overall_rating' => $overall, 'metrics' => $metrics,
            'recommendations' => $recs, 'capture_mode' => $captureMode,
            'coverage_stats' => $stats, 'convergence' => $convergence,
            'point_density' => $density,
        ];
    }
}
