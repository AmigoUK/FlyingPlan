<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\ExportFormats;

/**
 * @internal
 */
final class ExportFormatsTest extends CIUnitTestCase
{
    private function sampleWaypoints(): array
    {
        return [
            [
                'index' => 0, 'lat' => 51.5074, 'lng' => -0.1278,
                'altitude_m' => 50.0, 'speed_ms' => 5.0,
                'heading_deg' => 90.0, 'gimbal_pitch_deg' => -45.0,
                'turn_mode' => 'coordinatedTurn', 'hover_time_s' => 0.0,
                'action_type' => 'takePhoto',
            ],
            [
                'index' => 1, 'lat' => 51.5084, 'lng' => -0.1268,
                'altitude_m' => 60.0, 'speed_ms' => 8.0,
                'heading_deg' => null, 'gimbal_pitch_deg' => -90.0,
                'turn_mode' => 'coordinatedTurn', 'hover_time_s' => 2.0,
                'action_type' => null,
            ],
        ];
    }

    // ── KML ──────────────────────────────────────────────────────
    public function testKmlIsValidXml(): void
    {
        $kml = ExportFormats::generateKml($this->sampleWaypoints(), 'REF-001');

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($kml), 'KML should be valid XML');

        $this->assertStringContainsString('REF-001', $kml);
        $this->assertStringContainsString('kml', $kml);
    }

    public function testKmlContainsWaypoints(): void
    {
        $kml = ExportFormats::generateKml($this->sampleWaypoints(), 'REF-001');

        $this->assertStringContainsString('WP 0', $kml);
        $this->assertStringContainsString('WP 1', $kml);
        $this->assertStringContainsString('51.5074', $kml);
    }

    // ── GeoJSON ──────────────────────────────────────────────────
    public function testGeojsonIsValidJson(): void
    {
        $json = ExportFormats::generateGeojson($this->sampleWaypoints(), 'REF-002');

        $data = json_decode($json, true);
        $this->assertNotNull($data, 'GeoJSON should be valid JSON');
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertEquals('REF-002', $data['properties']['reference']);
    }

    public function testGeojsonFeatureCount(): void
    {
        $json = ExportFormats::generateGeojson($this->sampleWaypoints(), 'REF-002');
        $data = json_decode($json, true);

        // 2 waypoints + 1 LineString route = 3 features
        $this->assertCount(3, $data['features']);
    }

    public function testGeojsonRouteLineString(): void
    {
        $json = ExportFormats::generateGeojson($this->sampleWaypoints(), 'REF-002');
        $data = json_decode($json, true);

        $route = $data['features'][0];
        $this->assertEquals('LineString', $route['geometry']['type']);
        $this->assertCount(2, $route['geometry']['coordinates']);
    }

    // ── CSV ──────────────────────────────────────────────────────
    public function testCsvFormat(): void
    {
        $csv = ExportFormats::generateCsv($this->sampleWaypoints());

        $lines = explode("\n", $csv);
        $this->assertCount(3, $lines, '1 header + 2 data rows');
        $this->assertStringStartsWith('index,lat,lng,', $lines[0]);

        // Check first data row has correct lat
        $this->assertStringContainsString('51.5074', $lines[1]);
    }

    public function testCsvColumnCount(): void
    {
        $csv = ExportFormats::generateCsv($this->sampleWaypoints());
        $lines = explode("\n", $csv);

        $headerCols = explode(',', $lines[0]);
        $dataCols = explode(',', $lines[1]);
        $this->assertCount(count($headerCols), $dataCols, 'Data columns should match header');
    }

    // ── GPX ──────────────────────────────────────────────────────
    public function testGpxIsValidXml(): void
    {
        $gpx = ExportFormats::generateGpx($this->sampleWaypoints(), 'REF-003');

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($gpx), 'GPX should be valid XML');
        $this->assertStringContainsString('gpx', $gpx);
        $this->assertStringContainsString('REF-003', $gpx);
    }

    public function testGpxContainsTrackAndWaypoints(): void
    {
        $gpx = ExportFormats::generateGpx($this->sampleWaypoints(), 'REF-003');

        // Should have track points and waypoints
        $this->assertStringContainsString('trkpt', $gpx);
        $this->assertStringContainsString('wpt', $gpx);
        $this->assertStringContainsString('51.5074', $gpx);
    }

    // ── Enhanced GeoJSON ─────────────────────────────────────────
    public function testEnhancedGeojsonHasFootprints(): void
    {
        $json = ExportFormats::generateEnhancedGeojson($this->sampleWaypoints(), 'REF-004', 'mini_4_pro');
        $data = json_decode($json, true);

        $this->assertNotNull($data);
        $this->assertTrue($data['properties']['enhanced']);
        $this->assertEquals('mini_4_pro', $data['properties']['drone_model']);

        // Should have footprint polygons
        $footprints = array_filter($data['features'], fn($f) =>
            ($f['properties']['type'] ?? '') === 'footprint'
        );
        $this->assertCount(2, $footprints, 'Each waypoint should have a footprint polygon');

        foreach ($footprints as $fp) {
            $this->assertEquals('Polygon', $fp['geometry']['type']);
        }
    }
}
