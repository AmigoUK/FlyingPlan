<?php

namespace App\Services;

/**
 * UK CAA Category Determination Engine.
 *
 * Pure-function module implementing the decision tree from UK Reg EU 2019/947,
 * ANO 2016, CAP 722, and CAP 3017 to determine operational category based on
 * drone class, pilot qualifications, and flight parameters.
 *
 * Ported from services/category_engine.py.
 */

// ── Input Types ──────────────────────────────────────────────────

class DroneProfile
{
    public string $class_mark;
    public int $mtom_grams;
    public bool $has_camera;
    public string $green_light_type;
    public int $green_light_weight_grams;
    public bool $has_low_speed_mode;
    public bool $remote_id_capable;

    public function __construct(
        string $class_mark = 'legacy',
        int $mtom_grams = 0,
        bool $has_camera = true,
        string $green_light_type = 'none',
        int $green_light_weight_grams = 0,
        bool $has_low_speed_mode = false,
        bool $remote_id_capable = false,
    ) {
        $this->class_mark = $class_mark;
        $this->mtom_grams = $mtom_grams;
        $this->has_camera = $has_camera;
        $this->green_light_type = $green_light_type;
        $this->green_light_weight_grams = $green_light_weight_grams;
        $this->has_low_speed_mode = $has_low_speed_mode;
        $this->remote_id_capable = $remote_id_capable;
    }

    public function effectiveMtomGrams(): int
    {
        $base = $this->mtom_grams ?: 0;
        if ($this->green_light_type === 'external') {
            $base += ($this->green_light_weight_grams ?: 0);
        }
        return $base;
    }
}


class PilotProfile
{
    public bool $has_flyer_id;
    public bool $has_a2_cofc;
    public ?string $gvc_level;
    public ?string $oa_type;
    public bool $has_insurance;

    public function __construct(
        bool $has_flyer_id = true,
        bool $has_a2_cofc = false,
        ?string $gvc_level = null,
        ?string $oa_type = null,
        bool $has_insurance = true,
    ) {
        $this->has_flyer_id = $has_flyer_id;
        $this->has_a2_cofc = $has_a2_cofc;
        $this->gvc_level = $gvc_level;
        $this->oa_type = $oa_type;
        $this->has_insurance = $has_insurance;
    }
}


class FlightParams
{
    public string $time_of_day;
    public string $proximity_to_people;
    public string $environment_type;
    public string $proximity_to_buildings;
    public string $airspace_type;
    public string $vlos_type;
    public string $speed_mode;

    public function __construct(
        string $time_of_day = 'day',
        string $proximity_to_people = '50m_plus',
        string $environment_type = 'open_countryside',
        string $proximity_to_buildings = 'over_150m',
        string $airspace_type = 'uncontrolled',
        string $vlos_type = 'vlos',
        string $speed_mode = 'normal',
    ) {
        $this->time_of_day = $time_of_day;
        $this->proximity_to_people = $proximity_to_people;
        $this->environment_type = $environment_type;
        $this->proximity_to_buildings = $proximity_to_buildings;
        $this->airspace_type = $airspace_type;
        $this->vlos_type = $vlos_type;
        $this->speed_mode = $speed_mode;
    }
}


// ── Output Type ──────────────────────────────────────────────────

class CategoryResult
{
    public string $category = '';
    public array $blockers = [];
    public array $warnings = [];
    public array $required_sections = [];
    public int $min_distance_people_m = 0;
    public int $min_distance_buildings_m = 0;
    public bool $can_overfly_people = false;
    public array $registration_reqs = [];
    public bool $is_legal_ra_required = false;
    public array $legal_notes = [];
}


// ── Engine ───────────────────────────────────────────────────────

class CategoryEngine
{
    public const BASE_SECTIONS = [
        'site_assessment', 'airspace_check', 'weather_assessment',
        'equipment_check', 'flight_plan_summary', 'imsafe', 'permissions',
        'emergency_procedures',
    ];

    /**
     * Determine operational category based on drone, pilot, and flight parameters.
     *
     * Decision tree (in order):
     * 1. MTOM >= 25kg -> Certified (blocker)
     * 2. BVLOS or over crowds or controlled/restricted/danger airspace -> Specific
     * 3. C0 or Legacy < 250g -> open_a1
     * 4. C1 (< 900g) -> open_a1
     * 5. C2 (< 4kg) + A2 CofC -> open_a2
     * 6. Legacy < 2kg + A2 CofC -> open_a2 (50m, no reduction)
     * 7. Everything else < 25kg -> open_a3
     */
    public static function determineCategory(
        DroneProfile $drone,
        PilotProfile $pilot,
        FlightParams $flight
    ): CategoryResult {
        $result = new CategoryResult();
        $result->required_sections = array_values(self::BASE_SECTIONS);
        $effectiveMtom = $drone->effectiveMtomGrams();

        // ── Step 1: Certified (>= 25kg) ─────────────────────────
        if ($effectiveMtom >= 25000) {
            $result->category = 'certified';
            $result->blockers[] = 'Drone MTOM >= 25kg — Certified category operations are out of scope for this system.';
            $result->is_legal_ra_required = true;
            self::applyRegistrationReqs($result, $drone);
            return $result;
        }

        // ── Step 2: Specific category triggers ──────────────────
        $needsSpecific = false;
        $specificReason = [];

        if (in_array($flight->vlos_type, ['bvlos', 'extended_vlos'], true)) {
            $needsSpecific = true;
            $specificReason[] = strtoupper($flight->vlos_type) . ' flight requires Specific category';
        }

        if ($flight->proximity_to_people === 'over_crowds') {
            $needsSpecific = true;
            $specificReason[] = 'Flight over assemblies of people requires Specific category';
        }

        if (in_array($flight->airspace_type, ['controlled', 'restricted', 'danger'], true)) {
            $needsSpecific = true;
            $specificReason[] = ucfirst($flight->airspace_type) . ' airspace requires Specific category or special clearance';
        }

        if ($needsSpecific) {
            return self::resolveSpecific($result, $drone, $pilot, $flight, $specificReason);
        }

        // ── Step 3: Open A1 — C0 or Legacy < 250g ──────────────
        if ($drone->class_mark === 'C0' || ($drone->class_mark === 'legacy' && $effectiveMtom < 250)) {
            $result->category = 'open_a1';
            $result->min_distance_people_m = 0;
            $result->min_distance_buildings_m = 0;
            $result->can_overfly_people = true;
            $result->legal_notes[] = 'Open A1: May fly over uninvolved people but never over assemblies of people.';
            if ($effectiveMtom < 250 && !$drone->has_camera) {
                $result->legal_notes[] = 'Sub-250g without camera: Flyer ID not required.';
            }
            self::applyOpenCommon($result, $drone, $pilot, $flight);
            return $result;
        }

        // ── Step 4: Open A1 — C1 (< 900g) ──────────────────────
        if ($drone->class_mark === 'C1' && $effectiveMtom < 900) {
            $result->category = 'open_a1';
            $result->min_distance_people_m = 0;
            $result->min_distance_buildings_m = 0;
            $result->can_overfly_people = true;
            $result->legal_notes[] = 'Open A1 (C1): May fly close to people but should not intentionally overfly uninvolved people.';
            self::applyOpenCommon($result, $drone, $pilot, $flight);
            return $result;
        }

        // ── Step 5: Open A2 — C2 (< 4kg) + A2 CofC ────────────
        if ($drone->class_mark === 'C2' && $effectiveMtom < 4000 && $pilot->has_a2_cofc) {
            $result->category = 'open_a2';
            $result->required_sections[] = 'a2_assessment';
            if ($flight->speed_mode === 'low_speed' && $drone->has_low_speed_mode) {
                $result->min_distance_people_m = 5;
                $result->legal_notes[] = 'Open A2 (C2 low-speed mode): 5m minimum distance from people.';
            } else {
                $result->min_distance_people_m = 30;
                $result->legal_notes[] = 'Open A2 (C2): 30m minimum horizontal distance from uninvolved people.';
            }
            $result->min_distance_buildings_m = 0;
            $result->can_overfly_people = false;
            self::applyOpenCommon($result, $drone, $pilot, $flight);
            return $result;
        }

        // ── Step 6: Open A2 — Legacy < 2kg + A2 CofC ───────────
        if ($drone->class_mark === 'legacy' && $effectiveMtom < 2000 && $pilot->has_a2_cofc) {
            $result->category = 'open_a2';
            $result->required_sections[] = 'a2_assessment';
            $result->min_distance_people_m = 50;
            $result->min_distance_buildings_m = 0;
            $result->can_overfly_people = false;
            $result->legal_notes[] = 'Open A2 (Legacy < 2kg): 50m minimum distance from people. No low-speed reduction available.';
            self::applyOpenCommon($result, $drone, $pilot, $flight);
            return $result;
        }

        // ── Step 7: Open A3 — everything else < 25kg ────────────
        $result->category = 'open_a3';
        $result->required_sections[] = 'a3_assessment';
        $result->min_distance_people_m = 50;
        $result->min_distance_buildings_m = 50;
        $result->can_overfly_people = false;
        $result->legal_notes[] = 'Open A3: 150m from residential, commercial, industrial or recreational areas.';
        $result->legal_notes[] = 'Open A3: 50m from uninvolved people and 50m from buildings.';

        // Check if pilot could have qualified for A2 but is missing cert
        if ($drone->class_mark === 'C2' && $effectiveMtom < 4000 && !$pilot->has_a2_cofc) {
            $result->warnings[] = 'This C2 drone could operate in Open A2 if you obtain an A2 Certificate of Competency.';
        }
        if ($drone->class_mark === 'legacy' && $effectiveMtom < 2000 && !$pilot->has_a2_cofc) {
            $result->warnings[] = 'This legacy drone (< 2kg) could operate in Open A2 if you obtain an A2 Certificate of Competency.';
        }

        self::applyOpenCommon($result, $drone, $pilot, $flight);
        return $result;
    }

    // ── Helper: Resolve Specific Category ────────────────────────

    private static function resolveSpecific(
        CategoryResult $result,
        DroneProfile $drone,
        PilotProfile $pilot,
        FlightParams $flight,
        array $reasons
    ): CategoryResult {
        $result->legal_notes = array_merge($result->legal_notes, $reasons);
        $result->is_legal_ra_required = true;

        if ($pilot->oa_type === 'PDRA_01') {
            $result->category = 'specific_pdra01';
            $result->required_sections[] = 'specific_ops';
            $result->legal_notes[] = 'Specific (PDRA-01): Operating under Predefined Risk Assessment.';
        } elseif ($pilot->oa_type === 'FULL_SORA') {
            $result->category = 'specific_sora';
            $result->required_sections[] = 'specific_ops';
            $result->legal_notes[] = 'Specific (SORA): Operating under full Specific Operations Risk Assessment.';
        } else {
            $result->category = 'specific_sora';
            $result->blockers[] =
                'This flight requires Specific category but you have no Operational Authorisation (OA). '
                . 'You need a PDRA-01 or full SORA OA from the CAA to proceed.';
        }

        if (!$pilot->has_insurance) {
            $result->blockers[] = 'Third-party liability insurance is mandatory for Specific category operations.';
        }

        if ($pilot->gvc_level === null && $pilot->oa_type !== 'FULL_SORA') {
            $result->warnings[] = 'A GVC or equivalent qualification is typically required for Specific category operations.';
        }

        self::applyNightChecks($result, $drone, $flight);
        self::applyRegistrationReqs($result, $drone);
        self::applyProximityChecks($result, $drone, $flight);

        return $result;
    }

    // ── Helper: Common Open Category Logic ───────────────────────

    private static function applyOpenCommon(
        CategoryResult $result,
        DroneProfile $drone,
        PilotProfile $pilot,
        FlightParams $flight
    ): void {
        $result->is_legal_ra_required = false;
        array_unshift(
            $result->legal_notes,
            'Open Category: A pre-flight checklist is good practice but not a legal requirement under Article 11.'
        );

        self::applyNightChecks($result, $drone, $flight);
        self::applyRegistrationReqs($result, $drone);
        self::applyProximityChecks($result, $drone, $flight);

        if ($flight->airspace_type === 'frz') {
            $result->warnings[] = 'Flight Restriction Zone: Check for drone permissions using NATS Drone Assist or Altitude Angel.';
        }
    }

    // ── Night Flying ────────────────────────────────────────────

    private static function applyNightChecks(
        CategoryResult $result,
        DroneProfile $drone,
        FlightParams $flight
    ): void {
        if (!in_array($flight->time_of_day, ['night', 'twilight'], true)) {
            return;
        }

        $result->required_sections[] = 'night_flying';

        if ($drone->green_light_type === 'none') {
            $result->blockers[] =
                'Night/twilight flight requires a green flashing light visible from the ground. '
                . 'No green light configured on this drone.';
        } else {
            $result->legal_notes[] =
                'Night flying: Green flashing light must be fitted, switched on, and visible from the ground (from Jan 2026).';

            // Check if external light pushes MTOM over a threshold
            if ($drone->green_light_type === 'external' && $drone->effectiveMtomGrams() !== $drone->mtom_grams) {
                $baseMtom = $drone->mtom_grams ?: 0;
                $effMtom = $drone->effectiveMtomGrams();
                $thresholds = [[250, '250g/C0'], [900, '900g/C1'], [4000, '4kg/C2']];
                foreach ($thresholds as [$threshold, $label]) {
                    if ($baseMtom < $threshold && $effMtom >= $threshold) {
                        $result->warnings[] =
                            "External green light ({$drone->green_light_weight_grams}g) pushes effective MTOM "
                            . "from {$baseMtom}g to {$effMtom}g, crossing the {$label} threshold. "
                            . 'Category may change.';
                        break;
                    }
                }
            }
        }
    }

    // ── Registration Requirements ───────────────────────────────

    private static function applyRegistrationReqs(CategoryResult $result, DroneProfile $drone): void
    {
        $effectiveMtom = $drone->effectiveMtomGrams();
        $reqs = [];

        // Flyer ID: required for >= 250g or any drone with camera
        if ($effectiveMtom >= 250 || $drone->has_camera) {
            $reqs['flyer_id'] = true;
        } elseif ($effectiveMtom >= 100) {
            $reqs['flyer_id'] = true;
            $result->warnings[] = 'Flyer ID required from Jan 2026 for drones >= 100g.';
        } else {
            $reqs['flyer_id'] = false;
        }

        // Operator ID: required for >= 250g or any drone with camera
        $reqs['operator_id'] = $effectiveMtom >= 250 || $drone->has_camera;

        // Remote ID: required from 2028, advisory now
        $reqs['remote_id'] = $drone->remote_id_capable;
        if (!$drone->remote_id_capable) {
            $result->warnings[] = 'Remote ID will be required from 2028. Consider upgrading.';
        }

        // Insurance: required for commercial operations
        $reqs['insurance'] = true;

        $result->registration_reqs = $reqs;
    }

    // ── Proximity Validation ────────────────────────────────────

    private static function applyProximityChecks(
        CategoryResult $result,
        DroneProfile $drone,
        FlightParams $flight
    ): void {
        if ($flight->proximity_to_people === 'over_crowds' && str_starts_with($result->category, 'open_')) {
            $result->blockers[] = 'Flight over assemblies of people is never permitted in Open category.';
        }

        if ($result->category === 'open_a3') {
            if (in_array($flight->environment_type, ['suburban', 'urban', 'congested'], true)) {
                if ($flight->proximity_to_buildings === 'under_50m') {
                    $result->blockers[] = 'Open A3: Must maintain 50m from buildings in residential/urban areas.';
                }
            }
        }
    }

    // ── Convenience Functions ────────────────────────────────────

    /**
     * Get registration requirements for a drone (standalone).
     */
    public static function getRegistrationRequirements(DroneProfile $drone): array
    {
        $result = new CategoryResult();
        self::applyRegistrationReqs($result, $drone);
        return $result->registration_reqs;
    }

    /**
     * Check night flying requirements (standalone).
     */
    public static function getNightRequirements(DroneProfile $drone, FlightParams $flight): array
    {
        $result = new CategoryResult();
        $result->required_sections = array_values(self::BASE_SECTIONS);
        self::applyNightChecks($result, $drone, $flight);
        return [
            'needs_green_light' => in_array($flight->time_of_day, ['night', 'twilight'], true),
            'has_green_light'   => $drone->green_light_type !== 'none',
            'blockers'          => $result->blockers,
            'warnings'          => $result->warnings,
        ];
    }

    /**
     * Validate proximity for a given category (standalone).
     */
    public static function validateProximity(
        string $category,
        string $proximityToPeople,
        string $environmentType,
        string $proximityToBuildings
    ): array {
        $blockers = [];
        if ($proximityToPeople === 'over_crowds' && str_starts_with($category, 'open_')) {
            $blockers[] = 'Flight over assemblies of people is never permitted in Open category.';
        }
        if ($category === 'open_a3') {
            if (in_array($environmentType, ['suburban', 'urban', 'congested'], true)) {
                if ($proximityToBuildings === 'under_50m') {
                    $blockers[] = 'Open A3: Must maintain 50m from buildings in residential/urban areas.';
                }
            }
        }
        return $blockers;
    }
}
