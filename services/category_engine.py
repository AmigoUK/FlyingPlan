"""UK CAA Category Determination Engine.

Pure-function module implementing the decision tree from UK Reg EU 2019/947,
ANO 2016, CAP 722, and CAP 3017 to determine operational category based on
drone class, pilot qualifications, and flight parameters.

No DB imports — operates on dataclasses.
"""

from dataclasses import dataclass, field


# ── Input Types ──────────────────────────────────────────────────

@dataclass
class DroneProfile:
    class_mark: str = 'legacy'   # 'C0','C1','C2','C3','C4','legacy'
    mtom_grams: int = 0
    has_camera: bool = True
    green_light_type: str = 'none'  # 'built_in','external','none'
    green_light_weight_grams: int = 0
    has_low_speed_mode: bool = False
    remote_id_capable: bool = False

    @property
    def effective_mtom_grams(self):
        base = self.mtom_grams or 0
        if self.green_light_type == 'external':
            base += (self.green_light_weight_grams or 0)
        return base


@dataclass
class PilotProfile:
    has_flyer_id: bool = True
    has_a2_cofc: bool = False
    gvc_level: str = None        # None,'GVC','RPC_L1'...'RPC_L4'
    oa_type: str = None          # None,'PDRA_01','FULL_SORA'
    has_insurance: bool = True


@dataclass
class FlightParams:
    time_of_day: str = 'day'           # 'day','night','twilight'
    proximity_to_people: str = '50m_plus'  # 'over_uninvolved','near_under_50m','50m_plus','over_crowds','controlled_area'
    environment_type: str = 'open_countryside'
    proximity_to_buildings: str = 'over_150m'
    airspace_type: str = 'uncontrolled'   # 'uncontrolled','frz','controlled','restricted','danger'
    vlos_type: str = 'vlos'               # 'vlos','extended_vlos','bvlos'
    speed_mode: str = 'normal'            # 'normal','low_speed','sport'


# ── Output Type ──────────────────────────────────────────────────

@dataclass
class CategoryResult:
    category: str = ''              # 'open_a1','open_a2','open_a3','specific_pdra01','specific_sora','certified'
    blockers: list = field(default_factory=list)      # hard stops
    warnings: list = field(default_factory=list)      # advisory
    required_sections: list = field(default_factory=list)  # which RA sections apply
    min_distance_people_m: int = 0
    min_distance_buildings_m: int = 0
    can_overfly_people: bool = False
    registration_reqs: dict = field(default_factory=dict)
    is_legal_ra_required: bool = False
    legal_notes: list = field(default_factory=list)


# ── Base RA sections (always shown) ─────────────────────────────

BASE_SECTIONS = [
    'site_assessment', 'airspace_check', 'weather_assessment',
    'equipment_check', 'flight_plan_summary', 'imsafe', 'permissions',
    'emergency_procedures',
]


# ── Core Decision Function ───────────────────────────────────────

def determine_category(drone: DroneProfile, pilot: PilotProfile, flight: FlightParams) -> CategoryResult:
    """Determine operational category based on drone, pilot, and flight parameters.

    Decision tree (in order):
    1. MTOM >= 25kg -> Certified (blocker)
    2. BVLOS or over crowds or controlled/restricted/danger airspace -> Specific
    3. C0 or Legacy < 250g -> open_a1
    4. C1 (< 900g) -> open_a1
    5. C2 (< 4kg) + A2 CofC -> open_a2
    6. Legacy < 2kg + A2 CofC -> open_a2 (50m, no reduction)
    7. Everything else < 25kg -> open_a3
    """
    result = CategoryResult()
    result.required_sections = list(BASE_SECTIONS)
    effective_mtom = drone.effective_mtom_grams

    # ── Step 1: Certified (>= 25kg) ─────────────────────────────
    if effective_mtom >= 25000:
        result.category = 'certified'
        result.blockers.append('Drone MTOM >= 25kg — Certified category operations are out of scope for this system.')
        result.is_legal_ra_required = True
        _apply_registration_reqs(result, drone)
        return result

    # ── Step 2: Specific category triggers ───────────────────────
    needs_specific = False
    specific_reason = []

    if flight.vlos_type in ('bvlos', 'extended_vlos'):
        needs_specific = True
        specific_reason.append(f'{flight.vlos_type.upper()} flight requires Specific category')

    if flight.proximity_to_people == 'over_crowds':
        needs_specific = True
        specific_reason.append('Flight over assemblies of people requires Specific category')

    if flight.airspace_type in ('controlled', 'restricted', 'danger'):
        needs_specific = True
        specific_reason.append(f'{flight.airspace_type.title()} airspace requires Specific category or special clearance')

    if needs_specific:
        return _resolve_specific(result, drone, pilot, flight, specific_reason)

    # ── Step 3: Open A1 — C0 or Legacy < 250g ───────────────────
    if drone.class_mark == 'C0' or (drone.class_mark == 'legacy' and effective_mtom < 250):
        result.category = 'open_a1'
        result.min_distance_people_m = 0
        result.min_distance_buildings_m = 0
        result.can_overfly_people = True  # C0 can overfly uninvolved people (not crowds)
        result.legal_notes.append('Open A1: May fly over uninvolved people but never over assemblies of people.')
        if effective_mtom < 250 and not drone.has_camera:
            result.legal_notes.append('Sub-250g without camera: Flyer ID not required.')
        _apply_open_common(result, drone, pilot, flight)
        return result

    # ── Step 4: Open A1 — C1 (< 900g) ───────────────────────────
    if drone.class_mark == 'C1' and effective_mtom < 900:
        result.category = 'open_a1'
        result.min_distance_people_m = 0
        result.min_distance_buildings_m = 0
        result.can_overfly_people = True  # Inadvertent overfly OK, not intentional
        result.legal_notes.append('Open A1 (C1): May fly close to people but should not intentionally overfly uninvolved people.')
        _apply_open_common(result, drone, pilot, flight)
        return result

    # ── Step 5: Open A2 — C2 (< 4kg) + A2 CofC ─────────────────
    if drone.class_mark == 'C2' and effective_mtom < 4000 and pilot.has_a2_cofc:
        result.category = 'open_a2'
        result.required_sections.append('a2_assessment')
        if flight.speed_mode == 'low_speed' and drone.has_low_speed_mode:
            result.min_distance_people_m = 5
            result.legal_notes.append('Open A2 (C2 low-speed mode): 5m minimum distance from people.')
        else:
            result.min_distance_people_m = 30
            result.legal_notes.append('Open A2 (C2): 30m minimum horizontal distance from uninvolved people.')
        result.min_distance_buildings_m = 0
        result.can_overfly_people = False
        _apply_open_common(result, drone, pilot, flight)
        return result

    # ── Step 6: Open A2 — Legacy < 2kg + A2 CofC ────────────────
    if drone.class_mark == 'legacy' and effective_mtom < 2000 and pilot.has_a2_cofc:
        result.category = 'open_a2'
        result.required_sections.append('a2_assessment')
        result.min_distance_people_m = 50
        result.min_distance_buildings_m = 0
        result.can_overfly_people = False
        result.legal_notes.append('Open A2 (Legacy < 2kg): 50m minimum distance from people. No low-speed reduction available.')
        _apply_open_common(result, drone, pilot, flight)
        return result

    # ── Step 7: Open A3 — everything else < 25kg ────────────────
    result.category = 'open_a3'
    result.required_sections.append('a3_assessment')
    result.min_distance_people_m = 50
    result.min_distance_buildings_m = 50
    result.can_overfly_people = False
    result.legal_notes.append('Open A3: 150m from residential, commercial, industrial or recreational areas.')
    result.legal_notes.append('Open A3: 50m from uninvolved people and 50m from buildings.')

    # Check if pilot could have qualified for A2 but is missing cert
    if drone.class_mark == 'C2' and effective_mtom < 4000 and not pilot.has_a2_cofc:
        result.warnings.append('This C2 drone could operate in Open A2 if you obtain an A2 Certificate of Competency.')
    if drone.class_mark == 'legacy' and effective_mtom < 2000 and not pilot.has_a2_cofc:
        result.warnings.append('This legacy drone (< 2kg) could operate in Open A2 if you obtain an A2 Certificate of Competency.')

    _apply_open_common(result, drone, pilot, flight)
    return result


# ── Helper: Resolve Specific Category ────────────────────────────

def _resolve_specific(result, drone, pilot, flight, reasons):
    """Determine which Specific sub-category applies."""
    result.legal_notes.extend(reasons)
    result.is_legal_ra_required = True

    if pilot.oa_type == 'PDRA_01':
        result.category = 'specific_pdra01'
        result.required_sections.append('specific_ops')
        result.legal_notes.append('Specific (PDRA-01): Operating under Predefined Risk Assessment.')
    elif pilot.oa_type == 'FULL_SORA':
        result.category = 'specific_sora'
        result.required_sections.append('specific_ops')
        result.legal_notes.append('Specific (SORA): Operating under full Specific Operations Risk Assessment.')
    else:
        result.category = 'specific_sora'
        result.blockers.append(
            'This flight requires Specific category but you have no Operational Authorisation (OA). '
            'You need a PDRA-01 or full SORA OA from the CAA to proceed.'
        )

    if not pilot.has_insurance:
        result.blockers.append('Third-party liability insurance is mandatory for Specific category operations.')

    if pilot.gvc_level is None and pilot.oa_type != 'FULL_SORA':
        result.warnings.append('A GVC or equivalent qualification is typically required for Specific category operations.')

    # Night / registration still apply
    _apply_night_checks(result, drone, flight)
    _apply_registration_reqs(result, drone)
    _apply_proximity_checks(result, drone, flight)

    return result


# ── Helper: Common Open Category Logic ───────────────────────────

def _apply_open_common(result, drone, pilot, flight):
    """Apply checks common to all Open category operations."""
    result.is_legal_ra_required = False
    result.legal_notes.insert(0, 'Open Category: A pre-flight checklist is good practice but not a legal requirement under Article 11.')

    _apply_night_checks(result, drone, flight)
    _apply_registration_reqs(result, drone)
    _apply_proximity_checks(result, drone, flight)

    # FRZ check
    if flight.airspace_type == 'frz':
        result.warnings.append('Flight Restriction Zone: Check for drone permissions using NATS Drone Assist or Altitude Angel.')


# ── Night Flying ─────────────────────────────────────────────────

def _apply_night_checks(result, drone, flight):
    """Check green light requirement for night/twilight flights."""
    if flight.time_of_day in ('night', 'twilight'):
        result.required_sections.append('night_flying')
        if drone.green_light_type == 'none':
            result.blockers.append(
                'Night/twilight flight requires a green flashing light visible from the ground. '
                'No green light configured on this drone.'
            )
        else:
            result.legal_notes.append(
                'Night flying: Green flashing light must be fitted, switched on, and visible from the ground (from Jan 2026).'
            )
            # Check if external light pushes MTOM over a threshold
            if drone.green_light_type == 'external' and drone.effective_mtom_grams != drone.mtom_grams:
                base_mtom = drone.mtom_grams or 0
                eff_mtom = drone.effective_mtom_grams
                # Check if it crosses a category boundary
                for threshold, label in [(250, '250g/C0'), (900, '900g/C1'), (4000, '4kg/C2')]:
                    if base_mtom < threshold <= eff_mtom:
                        result.warnings.append(
                            f'External green light ({drone.green_light_weight_grams}g) pushes effective MTOM '
                            f'from {base_mtom}g to {eff_mtom}g, crossing the {label} threshold. '
                            f'Category may change.'
                        )
                        break


# ── Registration Requirements ────────────────────────────────────

def _apply_registration_reqs(result, drone):
    """Determine Flyer ID, Operator ID, Remote ID, and insurance requirements."""
    effective_mtom = drone.effective_mtom_grams
    reqs = {}

    # Flyer ID: required for >= 250g or any drone with camera (from Jan 2026: >= 100g)
    if effective_mtom >= 250 or drone.has_camera:
        reqs['flyer_id'] = True
    elif effective_mtom >= 100:
        reqs['flyer_id'] = True
        result.warnings.append('Flyer ID required from Jan 2026 for drones >= 100g.')
    else:
        reqs['flyer_id'] = False

    # Operator ID: required for >= 250g or any drone with camera
    reqs['operator_id'] = effective_mtom >= 250 or drone.has_camera

    # Remote ID: required from 2028, advisory now
    reqs['remote_id'] = drone.remote_id_capable
    if not drone.remote_id_capable:
        result.warnings.append('Remote ID will be required from 2028. Consider upgrading.')

    # Insurance: required for commercial operations (always for Specific)
    reqs['insurance'] = True  # assumed commercial context

    result.registration_reqs = reqs


# ── Proximity Validation ─────────────────────────────────────────

def _apply_proximity_checks(result, drone, flight):
    """Validate proximity to people against category requirements."""
    # Over crowds is never allowed in Open
    if flight.proximity_to_people == 'over_crowds' and result.category.startswith('open_'):
        result.blockers.append('Flight over assemblies of people is never permitted in Open category.')

    # A3 residential area check
    if result.category == 'open_a3':
        if flight.environment_type in ('suburban', 'urban', 'congested'):
            if flight.proximity_to_buildings == 'under_50m':
                result.blockers.append('Open A3: Must maintain 50m from buildings in residential/urban areas.')


# ── Convenience Function ─────────────────────────────────────────

def get_registration_requirements(drone: DroneProfile) -> dict:
    """Get registration requirements for a drone (standalone)."""
    result = CategoryResult()
    _apply_registration_reqs(result, drone)
    return result.registration_reqs


def get_night_requirements(drone: DroneProfile, flight: FlightParams) -> dict:
    """Check night flying requirements (standalone)."""
    result = CategoryResult()
    result.required_sections = list(BASE_SECTIONS)
    _apply_night_checks(result, drone, flight)
    return {
        'needs_green_light': flight.time_of_day in ('night', 'twilight'),
        'has_green_light': drone.green_light_type != 'none',
        'blockers': result.blockers,
        'warnings': result.warnings,
    }


def validate_proximity(category: str, proximity_to_people: str, environment_type: str, proximity_to_buildings: str) -> list:
    """Validate proximity for a given category (standalone). Returns list of blocker strings."""
    blockers = []
    if proximity_to_people == 'over_crowds' and category.startswith('open_'):
        blockers.append('Flight over assemblies of people is never permitted in Open category.')
    if category == 'open_a3':
        if environment_type in ('suburban', 'urban', 'congested'):
            if proximity_to_buildings == 'under_50m':
                blockers.append('Open A3: Must maintain 50m from buildings in residential/urban areas.')
    return blockers
