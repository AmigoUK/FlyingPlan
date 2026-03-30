<?php

namespace App\Services;

/**
 * Multi-format export: KML, GeoJSON, CSV, GPX, Enhanced GeoJSON.
 * Ported from services/export_formats.py.
 */
class ExportFormats
{
    private const KML_NS = 'http://www.opengis.net/kml/2.2';
    private const GPX_NS = 'http://www.topografix.com/GPX/1/1';

    /**
     * Generate KML (Google Earth) from waypoints.
     */
    public static function generateKml(array $waypoints, string $reference = ''): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $kml = $doc->createElementNS(self::KML_NS, 'kml');
        $doc->appendChild($kml);
        $document = $doc->createElementNS(self::KML_NS, 'Document');
        $kml->appendChild($document);

        $name = $doc->createElementNS(self::KML_NS, 'name', $reference);
        $document->appendChild($name);

        // Route line
        $pmLine = $doc->createElementNS(self::KML_NS, 'Placemark');
        $document->appendChild($pmLine);
        $pmName = $doc->createElementNS(self::KML_NS, 'name', 'Flight Route');
        $pmLine->appendChild($pmName);
        $line = $doc->createElementNS(self::KML_NS, 'LineString');
        $pmLine->appendChild($line);
        $altMode = $doc->createElementNS(self::KML_NS, 'altitudeMode', 'relativeToGround');
        $line->appendChild($altMode);
        $coordParts = array_map(
            fn($w) => sprintf('%.7f,%.7f,%.1f', $w['lng'], $w['lat'], $w['altitude_m'] ?? 30),
            $waypoints
        );
        $coords = $doc->createElementNS(self::KML_NS, 'coordinates', implode(' ', $coordParts));
        $line->appendChild($coords);

        // Individual waypoints
        foreach ($waypoints as $w) {
            $pm = $doc->createElementNS(self::KML_NS, 'Placemark');
            $document->appendChild($pm);
            $n = $doc->createElementNS(self::KML_NS, 'name', 'WP ' . ($w['index'] ?? 0));
            $pm->appendChild($n);
            $desc = $doc->createElementNS(self::KML_NS, 'description',
                sprintf('Alt: %.1fm, Speed: %.1fm/s', $w['altitude_m'] ?? 30, $w['speed_ms'] ?? 5));
            $pm->appendChild($desc);
            $point = $doc->createElementNS(self::KML_NS, 'Point');
            $pm->appendChild($point);
            $am = $doc->createElementNS(self::KML_NS, 'altitudeMode', 'relativeToGround');
            $point->appendChild($am);
            $c = $doc->createElementNS(self::KML_NS, 'coordinates',
                sprintf('%.7f,%.7f,%.1f', $w['lng'], $w['lat'], $w['altitude_m'] ?? 30));
            $point->appendChild($c);
        }

        return $doc->saveXML();
    }

    /**
     * Generate GeoJSON from waypoints.
     */
    public static function generateGeojson(array $waypoints, string $reference = ''): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $features = [];

        if (count($waypoints) >= 2) {
            $features[] = [
                'type' => 'Feature',
                'properties' => ['name' => 'Flight Route', 'type' => 'route'],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => array_map(
                        fn($w) => [$w['lng'], $w['lat'], $w['altitude_m'] ?? 30],
                        $waypoints
                    ),
                ],
            ];
        }

        foreach ($waypoints as $w) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name'             => 'WP ' . ($w['index'] ?? 0),
                    'altitude_m'       => $w['altitude_m'] ?? 30,
                    'speed_ms'         => $w['speed_ms'] ?? 5,
                    'heading_deg'      => $w['heading_deg'] ?? null,
                    'gimbal_pitch_deg' => $w['gimbal_pitch_deg'] ?? -90,
                    'action_type'      => $w['action_type'] ?? null,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$w['lng'], $w['lat'], $w['altitude_m'] ?? 30],
                ],
            ];
        }

        $geojson = [
            'type'       => 'FeatureCollection',
            'properties' => ['reference' => $reference],
            'features'   => $features,
        ];

        return json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate CSV from waypoints.
     */
    public static function generateCsv(array $waypoints): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $lines = ['index,lat,lng,altitude_m,speed_ms,heading_deg,gimbal_pitch_deg,turn_mode,hover_time_s,action_type'];

        foreach ($waypoints as $w) {
            $lines[] = implode(',', [
                $w['index'] ?? 0,
                sprintf('%.7f', $w['lat']),
                sprintf('%.7f', $w['lng']),
                sprintf('%.1f', $w['altitude_m'] ?? 30),
                sprintf('%.1f', $w['speed_ms'] ?? 5),
                isset($w['heading_deg']) && $w['heading_deg'] !== null ? (string) $w['heading_deg'] : '',
                sprintf('%.1f', $w['gimbal_pitch_deg'] ?? -90),
                $w['turn_mode'] ?? '',
                sprintf('%.1f', $w['hover_time_s'] ?? 0),
                $w['action_type'] ?? '',
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Generate GPX (GPS Exchange Format) from waypoints.
     */
    public static function generateGpx(array $waypoints, string $reference = ''): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $gpx = $doc->createElementNS(self::GPX_NS, 'gpx');
        $gpx->setAttribute('version', '1.1');
        $gpx->setAttribute('creator', 'FlyingPlan');
        $doc->appendChild($gpx);

        // Track
        $trk = $doc->createElementNS(self::GPX_NS, 'trk');
        $gpx->appendChild($trk);
        $name = $doc->createElementNS(self::GPX_NS, 'name', $reference);
        $trk->appendChild($name);
        $seg = $doc->createElementNS(self::GPX_NS, 'trkseg');
        $trk->appendChild($seg);

        foreach ($waypoints as $w) {
            $pt = $doc->createElementNS(self::GPX_NS, 'trkpt');
            $pt->setAttribute('lat', sprintf('%.7f', $w['lat']));
            $pt->setAttribute('lon', sprintf('%.7f', $w['lng']));
            $seg->appendChild($pt);
            $ele = $doc->createElementNS(self::GPX_NS, 'ele', sprintf('%.1f', $w['altitude_m'] ?? 30));
            $pt->appendChild($ele);
            $n = $doc->createElementNS(self::GPX_NS, 'name', 'WP ' . ($w['index'] ?? 0));
            $pt->appendChild($n);
        }

        // Waypoints
        foreach ($waypoints as $w) {
            $wpt = $doc->createElementNS(self::GPX_NS, 'wpt');
            $wpt->setAttribute('lat', sprintf('%.7f', $w['lat']));
            $wpt->setAttribute('lon', sprintf('%.7f', $w['lng']));
            $gpx->appendChild($wpt);
            $ele = $doc->createElementNS(self::GPX_NS, 'ele', sprintf('%.1f', $w['altitude_m'] ?? 30));
            $wpt->appendChild($ele);
            $n = $doc->createElementNS(self::GPX_NS, 'name', 'WP ' . ($w['index'] ?? 0));
            $wpt->appendChild($n);
        }

        return $doc->saveXML();
    }

    /**
     * Generate enhanced GeoJSON with camera footprint polygons.
     */
    public static function generateEnhancedGeojson(
        array $waypoints, string $reference = '', string $droneModel = 'mini_4_pro'
    ): string {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $features = [];

        if (count($waypoints) >= 2) {
            $features[] = [
                'type' => 'Feature',
                'properties' => ['name' => 'Flight Route', 'type' => 'route'],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => array_map(
                        fn($w) => [$w['lng'], $w['lat'], $w['altitude_m'] ?? 30],
                        $waypoints
                    ),
                ],
            ];
        }

        foreach ($waypoints as $w) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name'             => 'WP ' . ($w['index'] ?? 0),
                    'type'             => 'waypoint',
                    'altitude_m'       => $w['altitude_m'] ?? 30,
                    'speed_ms'         => $w['speed_ms'] ?? 5,
                    'heading_deg'      => $w['heading_deg'] ?? null,
                    'gimbal_pitch_deg' => $w['gimbal_pitch_deg'] ?? -90,
                    'action_type'      => $w['action_type'] ?? null,
                    'poi_lat'          => $w['poi_lat'] ?? null,
                    'poi_lng'          => $w['poi_lng'] ?? null,
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$w['lng'], $w['lat'], $w['altitude_m'] ?? 30],
                ],
            ];

            $footprint = CoverageAnalyzer::computePhotoFootprint(
                $w['lat'], $w['lng'],
                $w['altitude_m'] ?? 30,
                $w['gimbal_pitch_deg'] ?? -90,
                $w['heading_deg'] ?? 0,
                $droneModel
            );

            $ring = array_map(fn($p) => [$p[1], $p[0]], $footprint);
            $ring[] = $ring[0]; // close ring

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'name'           => 'Footprint WP ' . ($w['index'] ?? 0),
                    'type'           => 'footprint',
                    'waypoint_index' => $w['index'] ?? 0,
                ],
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [$ring],
                ],
            ];
        }

        $geojson = [
            'type'       => 'FeatureCollection',
            'properties' => ['reference' => $reference, 'enhanced' => true, 'drone_model' => $droneModel],
            'features'   => $features,
        ];

        return json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
