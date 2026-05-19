<?php

namespace App\Services;

/**
 * Terrain mesh generator for 3D mission preview (Three.js).
 * Ported from services/terrain_mesh.py.
 */
class TerrainMesh
{
    /**
     * Fetch elevation grid for the bounding box of given waypoints.
     */
    public static function getTerrainMesh(array $waypoints, int $resolution = 20): array
    {
        if (empty($waypoints)) {
            return ['elevations' => [], 'bounds' => [], 'rows' => 0, 'cols' => 0,
                    'min_elevation' => 0, 'max_elevation' => 0];
        }

        $lats = array_map(fn($w) => $w['lat'], $waypoints);
        $lngs = array_map(fn($w) => $w['lng'], $waypoints);

        $latRange = (max($lats) - min($lats)) ?: 0.001;
        $lngRange = (max($lngs) - min($lngs)) ?: 0.001;
        $margin = 0.1;

        $minLat = min($lats) - $latRange * $margin;
        $maxLat = max($lats) + $latRange * $margin;
        $minLng = min($lngs) - $lngRange * $margin;
        $maxLng = max($lngs) + $lngRange * $margin;

        $rows = $resolution;
        $cols = $resolution;
        $coords = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $lat = $minLat + ($maxLat - $minLat) * $r / max($rows - 1, 1);
                $lng = $minLng + ($maxLng - $minLng) * $c / max($cols - 1, 1);
                $coords[] = [$lat, $lng];
            }
        }

        $elevs = Elevation::getElevations($coords);

        $grid = [];
        $idx = 0;
        for ($r = 0; $r < $rows; $r++) {
            $row = [];
            for ($c = 0; $c < $cols; $c++) {
                $row[] = $elevs[$idx] ?? 0;
                $idx++;
            }
            $grid[] = $row;
        }

        $flat = array_merge(...$grid);

        return [
            'elevations' => $grid,
            'bounds' => [
                'min_lat' => $minLat, 'max_lat' => $maxLat,
                'min_lng' => $minLng, 'max_lng' => $maxLng,
            ],
            'rows' => $rows,
            'cols' => $cols,
            'min_elevation' => $flat ? min($flat) : 0,
            'max_elevation' => $flat ? max($flat) : 0,
        ];
    }
}
