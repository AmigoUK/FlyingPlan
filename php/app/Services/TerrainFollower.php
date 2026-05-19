<?php

namespace App\Services;

/**
 * Terrain-following mode: adjusts waypoint altitudes to maintain constant AGL.
 * Ported from services/terrain_follower.py.
 */
class TerrainFollower
{
    /**
     * Adjust waypoints to maintain constant AGL above terrain.
     */
    public static function applyTerrainFollowing(
        array $waypoints, float $targetAglM = 30, int $interpolationSamples = 5
    ): array {
        if (empty($waypoints)) {
            return [];
        }

        $coords = array_map(fn($w) => [$w['lat'], $w['lng']], $waypoints);
        $elevations = Elevation::getElevations($coords);

        $adjusted = [];
        $wpIndex = 0;

        foreach ($waypoints as $i => $wp) {
            $groundElev = $elevations[$i] ?? 0;

            $adj = $wp;
            $adj['index'] = $wpIndex;
            $adj['altitude_m'] = $groundElev + $targetAglM;
            $adj['ground_elevation_m'] = $groundElev;
            $adj['agl_m'] = $targetAglM;
            $adjusted[] = $adj;
            $wpIndex++;

            // Add intermediate points between this and next waypoint
            if ($i < count($waypoints) - 1 && $interpolationSamples > 0) {
                $nextWp = $waypoints[$i + 1];
                $pathData = Elevation::getPathElevations(
                    $wp['lat'], $wp['lng'],
                    $nextWp['lat'], $nextWp['lng'],
                    $interpolationSamples + 2
                );

                // Skip first and last (they are the original waypoints)
                $middle = array_slice($pathData, 1, -1);
                foreach ($middle as $pd) {
                    $interp = $wp;
                    $interp['index'] = $wpIndex;
                    $interp['lat'] = $pd['lat'];
                    $interp['lng'] = $pd['lng'];
                    $interp['altitude_m'] = ($pd['elevation'] ?? 0) + $targetAglM;
                    $interp['ground_elevation_m'] = $pd['elevation'] ?? 0;
                    $interp['agl_m'] = $targetAglM;
                    $interp['action_type'] = null;
                    $interp['hover_time_s'] = 0.0;
                    $adjusted[] = $interp;
                    $wpIndex++;
                }
            }
        }

        return $adjusted;
    }
}
