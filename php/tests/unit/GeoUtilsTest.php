<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\GeoUtils;

/**
 * @internal
 */
final class GeoUtilsTest extends CIUnitTestCase
{
    public function testHaversineKnownDistance(): void
    {
        // London (51.5074, -0.1278) to Paris (48.8566, 2.3522) ≈ 343.5 km
        $dist = GeoUtils::haversine(51.5074, -0.1278, 48.8566, 2.3522);
        $this->assertEqualsWithDelta(343500, $dist, 1000, 'London to Paris should be ~343.5 km');
    }

    public function testHaversineSamePoint(): void
    {
        $dist = GeoUtils::haversine(51.5074, -0.1278, 51.5074, -0.1278);
        $this->assertEqualsWithDelta(0, $dist, 0.01, 'Same point distance should be 0');
    }

    public function testHeadingNorth(): void
    {
        // Point due north
        $heading = GeoUtils::headingTo(51.0, 0.0, 52.0, 0.0);
        $this->assertEqualsWithDelta(0, $heading, 1.0, 'Due north should be ~0 degrees');
    }

    public function testHeadingEast(): void
    {
        $heading = GeoUtils::headingTo(51.0, 0.0, 51.0, 1.0);
        $this->assertEqualsWithDelta(90, $heading, 2.0, 'Due east should be ~90 degrees');
    }

    public function testHeadingSouth(): void
    {
        $heading = GeoUtils::headingTo(52.0, 0.0, 51.0, 0.0);
        $this->assertEqualsWithDelta(180, $heading, 1.0, 'Due south should be ~180 degrees');
    }

    public function testToMetresAndBack(): void
    {
        $centerLat = 51.5;
        $centerLng = -0.1;
        $testLat = 51.51;
        $testLng = -0.09;

        [$x, $y] = GeoUtils::toMetres($testLat, $testLng, $centerLat, $centerLng);
        [$backLat, $backLng] = GeoUtils::toLatLng($x, $y, $centerLat, $centerLng);

        $this->assertEqualsWithDelta($testLat, $backLat, 0.0001, 'Round-trip latitude');
        $this->assertEqualsWithDelta($testLng, $backLng, 0.0001, 'Round-trip longitude');
    }

    public function testRotate90(): void
    {
        [$rx, $ry] = GeoUtils::rotate(1.0, 0.0, M_PI / 2);
        $this->assertEqualsWithDelta(0.0, $rx, 0.0001);
        $this->assertEqualsWithDelta(1.0, $ry, 0.0001);
    }

    public function testOffsetPoint(): void
    {
        // Offset 1000m north from (51.5, -0.1)
        [$lat, $lng] = GeoUtils::offsetPoint(51.5, -0.1, 1000, 0);
        $dist = GeoUtils::haversine(51.5, -0.1, $lat, $lng);
        $this->assertEqualsWithDelta(1000, $dist, 5, 'Offset 1000m should produce ~1000m distance');
    }

    public function testPolygonCentroid(): void
    {
        $coords = [[0, 0], [0, 2], [2, 2], [2, 0]];
        [$cLat, $cLng] = GeoUtils::polygonCentroid($coords);
        $this->assertEqualsWithDelta(1.0, $cLat, 0.001);
        $this->assertEqualsWithDelta(1.0, $cLng, 0.001);
    }

    public function testPolygonCentroidEmpty(): void
    {
        [$cLat, $cLng] = GeoUtils::polygonCentroid([]);
        $this->assertEquals(0.0, $cLat);
        $this->assertEquals(0.0, $cLng);
    }
}
