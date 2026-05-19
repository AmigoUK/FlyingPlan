<?php

namespace App\Services;

/**
 * Terrain elevation service using Open-Meteo Elevation API.
 * Free, no API key required.
 * Ported from services/elevation.py.
 */
class Elevation
{
    private const API_URL = 'https://api.open-meteo.com/v1/elevation';
    private static array $cache = [];

    /**
     * Get ground elevation for a list of [lat, lng] pairs.
     * @return float[] elevation values in metres
     */
    public static function getElevations(array $coordinates): array
    {
        if (empty($coordinates)) {
            return [];
        }

        $uncached = [];
        $uncachedIndices = [];
        $results = array_fill(0, count($coordinates), null);

        foreach ($coordinates as $i => [$lat, $lng]) {
            $key = round($lat, 5) . ',' . round($lng, 5);
            if (isset(self::$cache[$key])) {
                $results[$i] = self::$cache[$key];
            } else {
                $uncached[] = [$lat, $lng];
                $uncachedIndices[] = $i;
            }
        }

        if (empty($uncached)) {
            return $results;
        }

        // Batch API call (max 100 at a time)
        $batchSize = 100;
        for ($start = 0; $start < count($uncached); $start += $batchSize) {
            $batch = array_slice($uncached, $start, $batchSize);
            $lats = implode(',', array_map(fn($c) => sprintf('%.5f', $c[0]), $batch));
            $lngs = implode(',', array_map(fn($c) => sprintf('%.5f', $c[1]), $batch));

            try {
                $url = self::API_URL . '?' . http_build_query([
                    'latitude' => $lats,
                    'longitude' => $lngs,
                ]);

                $ctx = stream_context_create(['http' => ['timeout' => 10]]);
                $response = @file_get_contents($url, false, $ctx);

                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (!is_array($data)) {
                        log_message('warning', 'Elevation API returned invalid JSON');
                        continue;
                    }
                    $elevations = $data['elevation'] ?? [];

                    foreach ($elevations as $j => $elev) {
                        $idx = $uncachedIndices[$start + $j];
                        [$lat, $lng] = $uncached[$start + $j];
                        $key = round($lat, 5) . ',' . round($lng, 5);
                        self::$cache[$key] = $elev;
                        $results[$idx] = $elev;
                    }
                }
            } catch (\Exception $e) {
                // Fallback: 0 for failed lookups
            }

            // Fill any remaining nulls with 0
            for ($j = 0; $j < count($batch); $j++) {
                $idx = $uncachedIndices[$start + $j];
                if ($results[$idx] === null) {
                    $results[$idx] = 0.0;
                }
            }
        }

        return $results;
    }

    public static function getElevation(float $lat, float $lng): float
    {
        $result = self::getElevations([[$lat, $lng]]);
        return $result[0] ?? 0.0;
    }

    /**
     * Get elevations along a path between two points.
     */
    public static function getPathElevations(
        float $startLat, float $startLng, float $endLat, float $endLng,
        int $numSamples = 10
    ): array {
        $coords = [];
        for ($i = 0; $i < $numSamples; $i++) {
            $t = $i / max($numSamples - 1, 1);
            $coords[] = [
                $startLat + $t * ($endLat - $startLat),
                $startLng + $t * ($endLng - $startLng),
            ];
        }

        $elevations = self::getElevations($coords);

        $results = [];
        $totalDist = 0;
        foreach ($coords as $i => [$lat, $lng]) {
            if ($i > 0) {
                $totalDist += GeoUtils::haversine(
                    $coords[$i - 1][0], $coords[$i - 1][1], $lat, $lng
                );
            }
            $results[] = [
                'lat' => $lat, 'lng' => $lng,
                'elevation' => $elevations[$i],
                'distance_m' => round($totalDist, 1),
            ];
        }

        return $results;
    }

    /**
     * Enrich waypoints with ground elevation data.
     */
    public static function getWaypointElevations(array $waypoints): array
    {
        if (empty($waypoints)) {
            return [];
        }

        $coords = array_map(fn($w) => [$w['lat'], $w['lng']], $waypoints);
        $elevations = self::getElevations($coords);

        $result = [];
        foreach ($waypoints as $i => $wp) {
            $enriched = $wp;
            $enriched['ground_elevation_m'] = $elevations[$i];
            $enriched['amsl_m'] = ($elevations[$i] ?? 0) + ($wp['altitude_m'] ?? 30);
            $enriched['agl_m'] = $wp['altitude_m'] ?? 30;
            $result[] = $enriched;
        }

        return $result;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
