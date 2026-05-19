<?php

namespace App\Services;

/**
 * Static map image generator for PDF reports.
 *
 * Since the Python staticmap library is not available in PHP,
 * this generates a simple SVG-based map placeholder with waypoint positions
 * plotted relative to the bounding box. For production use, consider
 * integrating with MapBox Static API or php-osm-static-api.
 *
 * Returns a data:image/svg+xml;base64 URI for embedding in PDFs.
 */
class StaticMap
{
    /**
     * Generate a data URI of a simple map visualization for PDF embedding.
     *
     * @param object $flightPlan  Flight plan with location_lat, location_lng
     * @param array  $waypoints   Array of waypoint objects with lat, lng
     * @param array  $pois        Array of POI objects with lat, lng
     * @return string|null        data:image/svg+xml;base64,... URI or null
     */
    public static function generateStaticMapDataUri(
        object $flightPlan,
        array $waypoints = [],
        array $pois = []
    ): ?string {
        $hasPoints = false;
        $allLats = [];
        $allLngs = [];

        // Collect all points for bounding box
        if (!empty($flightPlan->location_lat) && !empty($flightPlan->location_lng)) {
            $allLats[] = (float) $flightPlan->location_lat;
            $allLngs[] = (float) $flightPlan->location_lng;
            $hasPoints = true;
        }

        foreach ($waypoints as $wp) {
            if (!empty($wp->lat) && !empty($wp->lng)) {
                $allLats[] = (float) $wp->lat;
                $allLngs[] = (float) $wp->lng;
                $hasPoints = true;
            }
        }

        foreach ($pois as $poi) {
            if (!empty($poi->lat) && !empty($poi->lng)) {
                $allLats[] = (float) $poi->lat;
                $allLngs[] = (float) $poi->lng;
                $hasPoints = true;
            }
        }

        if (!$hasPoints) {
            return null;
        }

        // Calculate bounding box with margin
        $minLat = min($allLats);
        $maxLat = max($allLats);
        $minLng = min($allLngs);
        $maxLng = max($allLngs);

        $latRange = ($maxLat - $minLat) ?: 0.001;
        $lngRange = ($maxLng - $minLng) ?: 0.001;
        $margin = 0.15;
        $minLat -= $latRange * $margin;
        $maxLat += $latRange * $margin;
        $minLng -= $lngRange * $margin;
        $maxLng += $lngRange * $margin;
        $latRange = $maxLat - $minLat;
        $lngRange = $maxLng - $minLng;

        $width = 800;
        $height = 500;

        // Convert lat/lng to SVG coordinates
        $toX = function (float $lng) use ($minLng, $lngRange, $width): float {
            return (($lng - $minLng) / $lngRange) * $width;
        };
        $toY = function (float $lat) use ($maxLat, $latRange, $height): float {
            return (($maxLat - $lat) / $latRange) * $height; // flip Y axis
        };

        // Build SVG
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect width="100%" height="100%" fill="#e8f4e8" />';

        // Grid lines
        $svg .= '<g stroke="#ccc" stroke-width="0.5" opacity="0.5">';
        for ($i = 1; $i < 10; $i++) {
            $x = $width * $i / 10;
            $y = $height * $i / 10;
            $svg .= '<line x1="' . $x . '" y1="0" x2="' . $x . '" y2="' . $height . '"/>';
            $svg .= '<line x1="0" y1="' . $y . '" x2="' . $width . '" y2="' . $y . '"/>';
        }
        $svg .= '</g>';

        // Route polyline
        if (count($waypoints) >= 2) {
            $points = [];
            foreach ($waypoints as $wp) {
                if (!empty($wp->lat) && !empty($wp->lng)) {
                    $points[] = round($toX((float) $wp->lng), 1) . ',' . round($toY((float) $wp->lat), 1);
                }
            }
            if (count($points) >= 2) {
                $svg .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="#28a745" stroke-width="2" opacity="0.8"/>';
            }
        }

        // Waypoint markers (green)
        foreach ($waypoints as $wp) {
            if (!empty($wp->lat) && !empty($wp->lng)) {
                $x = round($toX((float) $wp->lng), 1);
                $y = round($toY((float) $wp->lat), 1);
                $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="#28a745" stroke="white" stroke-width="1"/>';
            }
        }

        // POI markers (orange)
        foreach ($pois as $poi) {
            if (!empty($poi->lat) && !empty($poi->lng)) {
                $x = round($toX((float) $poi->lng), 1);
                $y = round($toY((float) $poi->lat), 1);
                $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="5" fill="#ff8c00" stroke="white" stroke-width="1"/>';
            }
        }

        // Customer location (red, larger)
        if (!empty($flightPlan->location_lat) && !empty($flightPlan->location_lng)) {
            $x = round($toX((float) $flightPlan->location_lng), 1);
            $y = round($toY((float) $flightPlan->location_lat), 1);
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="7" fill="red" stroke="white" stroke-width="2"/>';
        }

        // Coordinate labels
        $svg .= '<text x="5" y="15" font-size="10" fill="#666">' . number_format($maxLat, 4) . ', ' . number_format($minLng, 4) . '</text>';
        $svg .= '<text x="' . ($width - 5) . '" y="' . ($height - 5) . '" font-size="10" fill="#666" text-anchor="end">' . number_format($minLat, 4) . ', ' . number_format($maxLng, 4) . '</text>';

        // Legend
        $svg .= '<g transform="translate(10,' . ($height - 50) . ')">';
        $svg .= '<rect width="120" height="40" fill="white" opacity="0.8" rx="4"/>';
        $svg .= '<circle cx="15" cy="12" r="5" fill="red"/><text x="25" y="16" font-size="9">Location</text>';
        $svg .= '<circle cx="15" cy="26" r="4" fill="#28a745"/><text x="25" y="30" font-size="9">Waypoints</text>';
        $svg .= '<circle cx="75" cy="12" r="4" fill="#ff8c00"/><text x="85" y="16" font-size="9">POIs</text>';
        $svg .= '</g>';

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
