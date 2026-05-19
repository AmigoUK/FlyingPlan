<?php

namespace App\Services;

/**
 * UK airspace awareness service.
 * Ported from services/airspace.py.
 */
class Airspace
{
    public static function getAirspaceGeojson(): array
    {
        $geojsonPath = dirname(dirname(__DIR__)) . '/static/data/uk_airspace.geojson';
        if (file_exists($geojsonPath)) {
            $data = json_decode(file_get_contents($geojsonPath), true);
            if ($data) {
                return $data;
            }
        }
        return self::generateSampleAirspace();
    }

    /**
     * Check if a waypoint is within any restricted airspace.
     */
    public static function checkWaypointAirspace(float $lat, float $lng, ?array $airspaceData = null): array
    {
        if ($airspaceData === null) {
            $airspaceData = self::getAirspaceGeojson();
        }

        $violations = [];
        $features = $airspaceData['features'] ?? [];

        foreach ($features as $feature) {
            $props = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? [];

            if (($geometry['type'] ?? '') === 'Polygon') {
                $coords = $geometry['coordinates'][0];
                if (self::pointInPolygon($lat, $lng, $coords)) {
                    $violations[] = [
                        'name'        => $props['name'] ?? 'Unknown',
                        'type'        => $props['type'] ?? 'unknown',
                        'class'       => $props['class'] ?? '',
                        'upper_limit' => $props['upper_limit'] ?? '',
                        'lower_limit' => $props['lower_limit'] ?? '',
                    ];
                }
            } elseif (($geometry['type'] ?? '') === 'Point') {
                $center = $geometry['coordinates'];
                $radiusM = $props['radius_m'] ?? 0;
                if ($radiusM > 0) {
                    $dist = GeoUtils::haversine($lat, $lng, $center[1], $center[0]);
                    if ($dist <= $radiusM) {
                        $violations[] = [
                            'name'       => $props['name'] ?? 'Unknown',
                            'type'       => $props['type'] ?? 'unknown',
                            'class'      => $props['class'] ?? '',
                            'distance_m' => round($dist),
                        ];
                    }
                }
            }
        }

        return $violations;
    }

    /**
     * Check all waypoints against airspace restrictions.
     */
    public static function checkRouteAirspace(array $waypoints, ?array $airspaceData = null): array
    {
        if ($airspaceData === null) {
            $airspaceData = self::getAirspaceGeojson();
        }

        $results = [];
        foreach ($waypoints as $wp) {
            $idx = $wp['index'] ?? 0;
            $violations = self::checkWaypointAirspace($wp['lat'], $wp['lng'], $airspaceData);
            if (!empty($violations)) {
                $results[$idx] = $violations;
            }
        }

        return $results;
    }

    /**
     * Ray casting point-in-polygon. Coords are [lng, lat] (GeoJSON order).
     */
    private static function pointInPolygon(float $lat, float $lng, array $polygonCoords): bool
    {
        $n = count($polygonCoords);
        $inside = false;
        $j = $n - 1;

        for ($i = 0; $i < $n; $i++) {
            $xi = $polygonCoords[$i][0];
            $yi = $polygonCoords[$i][1];
            $xj = $polygonCoords[$j][0];
            $yj = $polygonCoords[$j][1];

            if ((($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }

    private static function generateSampleAirspace(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['name' => 'Heathrow FRZ', 'type' => 'FRZ', 'class' => 'prohibited',
                        'upper_limit' => 'FL060', 'lower_limit' => 'SFC', 'radius_m' => 5000],
                    'geometry' => ['type' => 'Point', 'coordinates' => [-0.4614, 51.4700]],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['name' => 'Gatwick FRZ', 'type' => 'FRZ', 'class' => 'prohibited',
                        'upper_limit' => 'FL060', 'lower_limit' => 'SFC', 'radius_m' => 5000],
                    'geometry' => ['type' => 'Point', 'coordinates' => [-0.1903, 51.1537]],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['name' => 'London CTR', 'type' => 'CTR', 'class' => 'controlled',
                        'upper_limit' => 'FL060', 'lower_limit' => 'SFC'],
                    'geometry' => ['type' => 'Polygon', 'coordinates' => [[
                        [-0.6, 51.3], [0.2, 51.3], [0.2, 51.7], [-0.6, 51.7], [-0.6, 51.3],
                    ]]],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['name' => 'Birmingham International FRZ', 'type' => 'FRZ', 'class' => 'prohibited',
                        'upper_limit' => 'FL060', 'lower_limit' => 'SFC', 'radius_m' => 5000],
                    'geometry' => ['type' => 'Point', 'coordinates' => [-1.7478, 52.4539]],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['name' => 'Manchester FRZ', 'type' => 'FRZ', 'class' => 'prohibited',
                        'upper_limit' => 'FL060', 'lower_limit' => 'SFC', 'radius_m' => 5000],
                    'geometry' => ['type' => 'Point', 'coordinates' => [-2.2750, 53.3537]],
                ],
            ],
        ];
    }
}
