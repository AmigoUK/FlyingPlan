<?php

namespace App\Services;

/**
 * Ground Sampling Distance (GSD) calculator for survey/mapping missions.
 * Ported from services/gsd_calculator.py.
 */
class GsdCalculator
{
    public static function calculateGsd(string $droneModel, float $altitudeM,
                                         int $overlapPct = 70, ?float $areaSqm = null): array
    {
        $profile = DroneProfiles::getProfile($droneModel);

        $sensorW = $profile['sensor_width_mm'];
        $sensorH = $profile['sensor_height_mm'];
        $focal   = $profile['focal_length_mm'];
        $imgW    = $profile['image_width_px'];
        $imgH    = $profile['image_height_px'];
        $maxFlightMin = $profile['max_flight_time_min'] ?? 30;

        $gsdWCm = ($sensorW * $altitudeM * 100) / ($focal * $imgW);
        $gsdHCm = ($sensorH * $altitudeM * 100) / ($focal * $imgH);
        $gsdCm  = max($gsdWCm, $gsdHCm);

        $footprintWM = ($sensorW * $altitudeM) / $focal;
        $footprintHM = ($sensorH * $altitudeM) / $focal;

        $overlapFactor = 1 - ($overlapPct / 100);
        $effectiveWM = $footprintWM * $overlapFactor;
        $effectiveHM = $footprintHM * $overlapFactor;

        $lineSpacingM     = $effectiveWM;
        $photoIntervalM   = $effectiveHM;

        $result = [
            'gsd_cm_per_px'    => round($gsdCm, 2),
            'footprint_width_m'  => round($footprintWM, 1),
            'footprint_height_m' => round($footprintHM, 1),
            'line_spacing_m'     => round($lineSpacingM, 1),
            'photo_interval_m'   => round($photoIntervalM, 1),
            'overlap_pct'        => $overlapPct,
            'altitude_m'         => $altitudeM,
            'drone_model'        => $droneModel,
            'drone_name'         => $profile['display_name'],
            'sensor_info'        => "{$sensorW}x{$sensorH}mm, {$focal}mm, {$imgW}x{$imgH}px",
        ];

        if ($gsdCm <= 1) {
            $result['quality_tier'] = 'Ultra High (< 1 cm/px)';
        } elseif ($gsdCm <= 2) {
            $result['quality_tier'] = 'High (1-2 cm/px)';
        } elseif ($gsdCm <= 5) {
            $result['quality_tier'] = 'Standard (2-5 cm/px)';
        } else {
            $result['quality_tier'] = 'Low (> 5 cm/px)';
        }

        if ($areaSqm && $areaSqm > 0) {
            $numLines = max(1, sqrt($areaSqm) / $lineSpacingM);
            $photosPerLine = max(1, sqrt($areaSqm) / $photoIntervalM);
            $totalPhotos = (int) ($numLines * $photosPerLine);
            $totalDistanceM = $numLines * sqrt($areaSqm);

            $flightTimeS = $totalDistanceM / 5 + $totalPhotos * 2;
            $flightTimeMin = $flightTimeS / 60;
            $batteryPct = min(100, ($flightTimeMin / $maxFlightMin) * 100);

            $result['estimated_photos']          = $totalPhotos;
            $result['estimated_flight_time_min']  = round($flightTimeMin, 1);
            $result['estimated_battery_pct']      = round($batteryPct, 0);
            $result['batteries_needed'] = max(1, (int) ($batteryPct / 80) + (fmod($batteryPct, 80) > 0 ? 1 : 0));
        }

        return $result;
    }

    public static function recommendAltitude(string $droneModel, float $targetGsdCm): float
    {
        $profile = DroneProfiles::getProfile($droneModel);
        $sensorW = $profile['sensor_width_mm'];
        $focal   = $profile['focal_length_mm'];
        $imgW    = $profile['image_width_px'];

        return round(($targetGsdCm * $focal * $imgW) / ($sensorW * 100), 1);
    }
}
