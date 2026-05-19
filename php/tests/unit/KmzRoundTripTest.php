<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\KmzGenerator;
use App\Services\KmzParser;

/**
 * KMZ round-trip test: generate → parse → verify waypoints match.
 *
 * @internal
 */
final class KmzRoundTripTest extends CIUnitTestCase
{
    private function sampleWaypoints(): array
    {
        return [
            [
                'index' => 0, 'lat' => 51.5074, 'lng' => -0.1278,
                'altitude_m' => 50.0, 'speed_ms' => 5.0,
                'heading_deg' => 90.0, 'gimbal_pitch_deg' => -45.0,
                'turn_mode' => 'toPointAndStopWithDiscontinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 2.0,
                'action_type' => 'takePhoto',
            ],
            [
                'index' => 1, 'lat' => 51.5084, 'lng' => -0.1268,
                'altitude_m' => 60.0, 'speed_ms' => 8.0,
                'heading_deg' => 180.0, 'gimbal_pitch_deg' => -90.0,
                'turn_mode' => 'toPointAndStopWithDiscontinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 0.0,
                'action_type' => null,
            ],
            [
                'index' => 2, 'lat' => 51.5094, 'lng' => -0.1258,
                'altitude_m' => 40.0, 'speed_ms' => 3.0,
                'heading_deg' => null, 'gimbal_pitch_deg' => -60.0,
                'turn_mode' => 'toPointAndStopWithDiscontinuityCurvature',
                'turn_damping_dist' => 0.0, 'hover_time_s' => 5.0,
                'action_type' => 'takePhoto',
            ],
        ];
    }

    public function testGenerateKmzIsValidZip(): void
    {
        $kmz = KmzGenerator::generateKmz($this->sampleWaypoints(), 'TEST-001', 'mini_4_pro');

        $this->assertNotEmpty($kmz, 'KMZ output should not be empty');

        // Verify it's a valid ZIP
        $tmpFile = tempnam(sys_get_temp_dir(), 'kmz_test_');
        file_put_contents($tmpFile, $kmz);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmpFile) === true, 'Output should be a valid ZIP');
        $this->assertNotFalse($zip->getFromName('wpmz/template.kml'), 'Should contain template.kml');
        $this->assertNotFalse($zip->getFromName('wpmz/waylines.wpml'), 'Should contain waylines.wpml');
        $zip->close();
        unlink($tmpFile);
    }

    public function testRoundTripWaypointCount(): void
    {
        $original = $this->sampleWaypoints();
        $kmz = KmzGenerator::generateKmz($original, 'TEST-002', 'mini_4_pro');
        $parsed = KmzParser::parseKmz($kmz);

        $this->assertNull($parsed['error'], 'Parse should not return an error');
        $this->assertCount(count($original), $parsed['waypoints'], 'Parsed waypoint count should match');
    }

    public function testRoundTripCoordinates(): void
    {
        $original = $this->sampleWaypoints();
        $kmz = KmzGenerator::generateKmz($original, 'TEST-003', 'mini_4_pro');
        $parsed = KmzParser::parseKmz($kmz);

        foreach ($parsed['waypoints'] as $i => $pw) {
            $this->assertEqualsWithDelta(
                $original[$i]['lat'], $pw['lat'], 0.0001,
                "Waypoint $i lat mismatch"
            );
            $this->assertEqualsWithDelta(
                $original[$i]['lng'], $pw['lng'], 0.0001,
                "Waypoint $i lng mismatch"
            );
            $this->assertEqualsWithDelta(
                $original[$i]['altitude_m'], $pw['altitude_m'], 0.1,
                "Waypoint $i altitude mismatch"
            );
            $this->assertEqualsWithDelta(
                $original[$i]['speed_ms'], $pw['speed_ms'], 0.1,
                "Waypoint $i speed mismatch"
            );
        }
    }

    public function testRoundTripHeading(): void
    {
        $original = $this->sampleWaypoints();
        $kmz = KmzGenerator::generateKmz($original, 'TEST-004', 'mini_4_pro');
        $parsed = KmzParser::parseKmz($kmz);

        // WP 0 has heading 90.0
        $this->assertEqualsWithDelta(90.0, $parsed['waypoints'][0]['heading_deg'], 0.1);
        // WP 1 has heading 180.0
        $this->assertEqualsWithDelta(180.0, $parsed['waypoints'][1]['heading_deg'], 0.1);
        // WP 2 has no heading (null)
        $this->assertNull($parsed['waypoints'][2]['heading_deg']);
    }

    public function testRoundTripDroneDetection(): void
    {
        $kmz = KmzGenerator::generateKmz($this->sampleWaypoints(), 'TEST-005', 'mini_4_pro');
        $parsed = KmzParser::parseKmz($kmz);
        $this->assertEquals('mini_4_pro', $parsed['drone_model']);

        $kmz2 = KmzGenerator::generateKmz($this->sampleWaypoints(), 'TEST-006', 'mavic_3');
        $parsed2 = KmzParser::parseKmz($kmz2);
        $this->assertEquals('mavic_3', $parsed2['drone_model']);
    }

    public function testParseInvalidFile(): void
    {
        $parsed = KmzParser::parseKmz('not a zip file');
        $this->assertNotNull($parsed['error']);
        $this->assertEmpty($parsed['waypoints']);
    }

    public function testRoundTripActionType(): void
    {
        $original = $this->sampleWaypoints();
        $kmz = KmzGenerator::generateKmz($original, 'TEST-007', 'mini_4_pro');
        $parsed = KmzParser::parseKmz($kmz);

        // WP 0 has takePhoto action
        $this->assertEquals('takePhoto', $parsed['waypoints'][0]['action_type']);
        // WP 1 has no action
        $this->assertNull($parsed['waypoints'][1]['action_type']);
    }
}
