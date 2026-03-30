<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\GsdCalculator;

/**
 * @internal
 */
final class GsdCalculatorTest extends CIUnitTestCase
{
    public function testMini4ProAt50m(): void
    {
        $result = GsdCalculator::calculateGsd('mini_4_pro', 50.0);

        $this->assertArrayHasKey('gsd_cm_per_px', $result);
        $this->assertArrayHasKey('footprint_width_m', $result);
        $this->assertArrayHasKey('drone_name', $result);
        $this->assertEquals('DJI Mini 4 Pro', $result['drone_name']);

        // GSD = (sensor_width_mm * altitude_m * 100) / (focal * image_width_px)
        // = (9.7 * 50 * 100) / (6.7 * 4032) = 48500 / 27014.4 = 1.80 (approx)
        $this->assertGreaterThan(1.0, $result['gsd_cm_per_px']);
        $this->assertLessThan(3.0, $result['gsd_cm_per_px']);
    }

    public function testMavic3At100m(): void
    {
        $result = GsdCalculator::calculateGsd('mavic_3', 100.0);

        $this->assertEquals('DJI Mavic 3', $result['drone_name']);
        $this->assertGreaterThan(0, $result['gsd_cm_per_px']);
        $this->assertGreaterThan(0, $result['footprint_width_m']);
        $this->assertGreaterThan(0, $result['footprint_height_m']);
    }

    public function testOverlapAffectsSpacing(): void
    {
        $result70 = GsdCalculator::calculateGsd('mini_4_pro', 50.0, 70);
        $result80 = GsdCalculator::calculateGsd('mini_4_pro', 50.0, 80);

        // Higher overlap = smaller line spacing
        $this->assertGreaterThan($result80['line_spacing_m'], $result70['line_spacing_m']);
    }

    public function testWithAreaEstimates(): void
    {
        $result = GsdCalculator::calculateGsd('mini_4_pro', 50.0, 70, 10000.0);

        $this->assertArrayHasKey('estimated_photos', $result);
        $this->assertArrayHasKey('estimated_flight_time_min', $result);
        $this->assertArrayHasKey('estimated_battery_pct', $result);
        $this->assertArrayHasKey('batteries_needed', $result);
        $this->assertGreaterThan(0, $result['estimated_photos']);
    }

    public function testQualityTiers(): void
    {
        // Very low altitude = ultra high quality
        $low = GsdCalculator::calculateGsd('mavic_3', 10.0);
        $this->assertStringContainsString('Ultra High', $low['quality_tier']);

        // Very high altitude = low quality (> 5cm/px needs altitude well above 120m)
        // mini_4_pro at 120m gives ~4.3cm so use a less capable calc scenario
        $high = GsdCalculator::calculateGsd('mini_4_pro', 120.0);
        // At 120m mini_4_pro gives Standard tier, not Low — adjust expectation
        $this->assertStringContainsString('Standard', $high['quality_tier']);
    }

    public function testRecommendAltitude(): void
    {
        // For mini_4_pro, target 2cm GSD
        $alt = GsdCalculator::recommendAltitude('mini_4_pro', 2.0);
        $this->assertGreaterThan(0, $alt);

        // Verify: calculating GSD at recommended altitude should give ~2cm
        $result = GsdCalculator::calculateGsd('mini_4_pro', $alt);
        $this->assertEqualsWithDelta(2.0, $result['gsd_cm_per_px'], 0.5);
    }

    public function testFallbackDroneModel(): void
    {
        // Unknown drone should fall back to mini_4_pro
        $result = GsdCalculator::calculateGsd('nonexistent_drone', 50.0);
        $this->assertEquals('DJI Mini 4 Pro', $result['drone_name']);
    }
}
