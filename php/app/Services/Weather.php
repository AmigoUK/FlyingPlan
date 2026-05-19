<?php

namespace App\Services;

/**
 * Weather service using Open-Meteo API (free, no API key required).
 * Ported from services/weather.py.
 */
class Weather
{
    private const FORECAST_API_URL = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Get current weather + 48h forecast for a location.
     */
    public static function getWeather(float $lat, float $lng): array
    {
        try {
            $params = http_build_query([
                'latitude'  => sprintf('%.4f', $lat),
                'longitude' => sprintf('%.4f', $lng),
                'current'   => 'temperature_2m,wind_speed_10m,wind_direction_10m,'
                             . 'wind_gusts_10m,precipitation,cloud_cover,visibility',
                'hourly'    => 'temperature_2m,wind_speed_10m,wind_direction_10m,'
                             . 'wind_gusts_10m,precipitation_probability,cloud_cover,visibility',
                'forecast_hours'  => 48,
                'wind_speed_unit' => 'kmh',
                'timezone'        => 'auto',
            ]);

            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $response = @file_get_contents(self::FORECAST_API_URL . '?' . $params, false, $ctx);

            if ($response === false) {
                return ['current' => null, 'hourly' => [], 'warnings' => [], 'error' => 'API request failed'];
            }

            $data = json_decode($response, true);
        } catch (\Exception $e) {
            return ['current' => null, 'hourly' => [], 'warnings' => [], 'error' => $e->getMessage()];
        }

        $currentData = $data['current'] ?? [];
        $current = [
            'temp_c'           => $currentData['temperature_2m'] ?? null,
            'wind_speed_kmh'   => $currentData['wind_speed_10m'] ?? null,
            'wind_dir_deg'     => $currentData['wind_direction_10m'] ?? null,
            'wind_gusts_kmh'   => $currentData['wind_gusts_10m'] ?? null,
            'precipitation_mm' => $currentData['precipitation'] ?? null,
            'cloud_cover_pct'  => $currentData['cloud_cover'] ?? null,
            'visibility_m'     => $currentData['visibility'] ?? null,
        ];

        $hourlyData = $data['hourly'] ?? [];
        $times = $hourlyData['time'] ?? [];
        $hourly = [];
        foreach ($times as $i => $t) {
            $hourly[] = [
                'time'            => $t,
                'temp_c'          => $hourlyData['temperature_2m'][$i] ?? null,
                'wind_speed_kmh'  => $hourlyData['wind_speed_10m'][$i] ?? null,
                'wind_dir_deg'    => $hourlyData['wind_direction_10m'][$i] ?? null,
                'wind_gusts_kmh'  => $hourlyData['wind_gusts_10m'][$i] ?? null,
                'precip_prob_pct' => $hourlyData['precipitation_probability'][$i] ?? null,
                'cloud_cover_pct' => $hourlyData['cloud_cover'][$i] ?? null,
                'visibility_m'    => $hourlyData['visibility'][$i] ?? null,
            ];
        }

        return ['current' => $current, 'hourly' => $hourly, 'warnings' => [], 'error' => null];
    }

    /**
     * Check weather against drone limits and return warnings.
     */
    public static function checkDroneWarnings(?array $weatherCurrent, ?array $droneProfile = null): array
    {
        if (empty($weatherCurrent)) {
            return [];
        }

        $warnings = [];
        $windKmh    = $weatherCurrent['wind_speed_kmh'] ?? 0;
        $gustsKmh   = $weatherCurrent['wind_gusts_kmh'] ?? 0;
        $precip     = $weatherCurrent['precipitation_mm'] ?? 0;
        $visibility = $weatherCurrent['visibility_m'] ?? 99999;

        $maxWindMs = 10.7;
        if ($droneProfile) {
            $maxWindMs = $droneProfile['max_wind_speed_ms'] ?? 10.7;
        }
        $maxWindKmh = $maxWindMs * 3.6;

        if ($windKmh > $maxWindKmh) {
            $warnings[] = sprintf('Wind speed (%.0f km/h) exceeds drone limit (%.0f km/h)', $windKmh, $maxWindKmh);
        } elseif ($windKmh > $maxWindKmh * 0.8) {
            $warnings[] = sprintf('Wind speed (%.0f km/h) near drone limit (%.0f km/h)', $windKmh, $maxWindKmh);
        }

        if ($gustsKmh > $maxWindKmh * 1.2) {
            $warnings[] = sprintf('Wind gusts (%.0f km/h) dangerous for this drone', $gustsKmh);
        }

        if ($precip > 0) {
            $warnings[] = sprintf('Precipitation detected (%.1f mm) — most drones are not waterproof', $precip);
        }

        if ($visibility < 1000) {
            $warnings[] = sprintf('Low visibility (%.0fm) — maintain VLOS', $visibility);
        } elseif ($visibility < 3000) {
            $warnings[] = sprintf('Reduced visibility (%.0fm)', $visibility);
        }

        return $warnings;
    }
}
