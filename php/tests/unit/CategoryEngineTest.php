<?php

// DroneProfile, PilotProfile, FlightParams are all defined in CategoryEngine.php
// PSR-4 autoloader won't find them by class name alone, so require the file.
require_once APPPATH . 'Services/CategoryEngine.php';

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\CategoryEngine;
use App\Services\DroneProfile;
use App\Services\PilotProfile;
use App\Services\FlightParams;

/**
 * CategoryEngine test — 12 test vectors covering all UK CAA categories.
 * These vectors were validated against the Python engine in Phase 3.
 *
 * @internal
 */
final class CategoryEngineTest extends CIUnitTestCase
{
    // ── Test 1: C0 drone, basic pilot → Open A1 ──────────────────
    public function testC0OpenA1(): void
    {
        $drone = new DroneProfile(class_mark: 'C0', mtom_grams: 200, has_camera: true);
        $pilot = new PilotProfile(has_flyer_id: true);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a1', $r->category);
        $this->assertEquals(0, $r->min_distance_people_m);
        $this->assertTrue($r->can_overfly_people);
        $this->assertEmpty($r->blockers);
    }

    // ── Test 2: Legacy < 250g → Open A1 ──────────────────────────
    public function testLegacySub250gOpenA1(): void
    {
        $drone = new DroneProfile(class_mark: 'legacy', mtom_grams: 240, has_camera: true);
        $pilot = new PilotProfile(has_flyer_id: true);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a1', $r->category);
        $this->assertTrue($r->can_overfly_people);
    }

    // ── Test 3: C1 < 900g → Open A1 ─────────────────────────────
    public function testC1OpenA1(): void
    {
        $drone = new DroneProfile(class_mark: 'C1', mtom_grams: 800);
        $pilot = new PilotProfile(has_flyer_id: true);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a1', $r->category);
        $this->assertEquals(0, $r->min_distance_people_m);
        $this->assertTrue($r->can_overfly_people);
    }

    // ── Test 4: C2 + A2 CofC → Open A2, 30m ────────────────────
    public function testC2WithA2CofcOpenA2(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500, has_low_speed_mode: true);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true);
        $flight = new FlightParams(speed_mode: 'normal');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a2', $r->category);
        $this->assertEquals(30, $r->min_distance_people_m);
        $this->assertFalse($r->can_overfly_people);
        $this->assertContains('a2_assessment', $r->required_sections);
    }

    // ── Test 5: C2 + A2 CofC + low speed → Open A2, 5m ─────────
    public function testC2LowSpeedOpenA2(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500, has_low_speed_mode: true);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true);
        $flight = new FlightParams(speed_mode: 'low_speed');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a2', $r->category);
        $this->assertEquals(5, $r->min_distance_people_m);
    }

    // ── Test 6: Legacy < 2kg + A2 CofC → Open A2, 50m ──────────
    public function testLegacySub2kgOpenA2(): void
    {
        $drone = new DroneProfile(class_mark: 'legacy', mtom_grams: 1500);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a2', $r->category);
        $this->assertEquals(50, $r->min_distance_people_m);
    }

    // ── Test 7: C3 / large legacy → Open A3 ─────────────────────
    public function testLargeOpenA3(): void
    {
        $drone = new DroneProfile(class_mark: 'legacy', mtom_grams: 5000);
        $pilot = new PilotProfile(has_flyer_id: true);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a3', $r->category);
        $this->assertEquals(50, $r->min_distance_people_m);
        $this->assertEquals(50, $r->min_distance_buildings_m);
        $this->assertFalse($r->can_overfly_people);
    }

    // ── Test 8: C2 without A2 CofC → Open A3 with warning ──────
    public function testC2WithoutA2FallsToA3(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: false);
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('open_a3', $r->category);
        $this->assertNotEmpty($r->warnings, 'Should warn about A2 CofC upgrade path');
    }

    // ── Test 9: BVLOS → Specific (no OA → blocker) ──────────────
    public function testBvlosSpecificBlocked(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true);
        $flight = new FlightParams(vlos_type: 'bvlos');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertStringStartsWith('specific_', $r->category);
        $this->assertNotEmpty($r->blockers, 'BVLOS without OA should have blockers');
        $this->assertTrue($r->is_legal_ra_required);
    }

    // ── Test 10: BVLOS + PDRA-01 → Specific PDRA-01 ─────────────
    public function testBvlosPdra01(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true, oa_type: 'PDRA_01', has_insurance: true);
        $flight = new FlightParams(vlos_type: 'bvlos');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('specific_pdra01', $r->category);
        $this->assertEmpty($r->blockers);
        $this->assertTrue($r->is_legal_ra_required);
    }

    // ── Test 11: BVLOS + SORA → Specific SORA ───────────────────
    public function testBvlosSora(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500);
        $pilot = new PilotProfile(has_flyer_id: true, has_a2_cofc: true, gvc_level: 'GVC', oa_type: 'FULL_SORA', has_insurance: true);
        $flight = new FlightParams(vlos_type: 'bvlos');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('specific_sora', $r->category);
        $this->assertEmpty($r->blockers);
    }

    // ── Test 12: >= 25kg → Certified (always blocked) ────────────
    public function testCertified(): void
    {
        $drone = new DroneProfile(class_mark: 'legacy', mtom_grams: 30000);
        $pilot = new PilotProfile();
        $flight = new FlightParams();

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $this->assertEquals('certified', $r->category);
        $this->assertNotEmpty($r->blockers, 'Certified should always have blockers');
        $this->assertTrue($r->is_legal_ra_required);
    }

    // ── Additional: Night flying blocker ─────────────────────────
    public function testNightFlyingWithoutGreenLight(): void
    {
        $drone = new DroneProfile(class_mark: 'C0', mtom_grams: 200, green_light_type: 'none');
        $pilot = new PilotProfile();
        $flight = new FlightParams(time_of_day: 'night');

        $r = CategoryEngine::determineCategory($drone, $pilot, $flight);
        $hasNightBlocker = false;
        foreach ($r->blockers as $b) {
            if (str_contains($b, 'green flashing light')) {
                $hasNightBlocker = true;
                break;
            }
        }
        $this->assertTrue($hasNightBlocker, 'Night flight without green light should be blocked');
    }

    // ── Additional: Registration requirements ────────────────────
    public function testRegistrationRequirements(): void
    {
        $drone = new DroneProfile(class_mark: 'C2', mtom_grams: 3500, has_camera: true);
        $reqs = CategoryEngine::getRegistrationRequirements($drone);

        $this->assertTrue($reqs['flyer_id']);
        $this->assertTrue($reqs['operator_id']);
        $this->assertTrue($reqs['insurance']);
    }

    // ── Additional: Proximity validation ─────────────────────────
    public function testProximityValidation(): void
    {
        $blockers = CategoryEngine::validateProximity('open_a3', 'over_crowds', 'urban', 'under_50m');
        $this->assertNotEmpty($blockers);

        $clean = CategoryEngine::validateProximity('open_a1', '50m_plus', 'open_countryside', 'over_150m');
        $this->assertEmpty($clean);
    }
}
