"""Tests for the UK CAA Category Determination Engine.

Pure unit tests — no Flask context required.
"""

import pytest
import sys
import os

# Add project root to path so we can import services
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from services.category_engine import (
    DroneProfile, PilotProfile, FlightParams, CategoryResult,
    determine_category, get_registration_requirements, get_night_requirements,
    validate_proximity,
)


# ── Helpers ──────────────────────────────────────────────────────

def _drone(class_mark='C0', mtom=249, **kw):
    return DroneProfile(class_mark=class_mark, mtom_grams=mtom, **kw)


def _pilot(**kw):
    defaults = dict(has_flyer_id=True, has_a2_cofc=False, gvc_level=None, oa_type=None, has_insurance=True)
    defaults.update(kw)
    return PilotProfile(**defaults)


def _flight(**kw):
    return FlightParams(**kw)


# ══════════════════════════════════════════════════════════════════
# Open A1 — C0 / sub-250g legacy / C1
# ══════════════════════════════════════════════════════════════════

class TestOpenA1:
    def test_c0_249g_daytime(self):
        result = determine_category(_drone('C0', 249), _pilot(), _flight())
        assert result.category == 'open_a1'
        assert result.min_distance_people_m == 0
        assert not result.blockers

    def test_c0_can_overfly_people(self):
        result = determine_category(_drone('C0', 200), _pilot(), _flight())
        assert result.can_overfly_people is True

    def test_legacy_under_250g(self):
        result = determine_category(_drone('legacy', 200), _pilot(), _flight())
        assert result.category == 'open_a1'

    def test_legacy_249g(self):
        result = determine_category(_drone('legacy', 249), _pilot(), _flight())
        assert result.category == 'open_a1'

    def test_c1_720g(self):
        result = determine_category(_drone('C1', 720), _pilot(), _flight())
        assert result.category == 'open_a1'
        assert result.min_distance_people_m == 0

    def test_c1_899g(self):
        result = determine_category(_drone('C1', 899), _pilot(), _flight())
        assert result.category == 'open_a1'

    def test_sub250_no_camera_flyer_id_note(self):
        result = determine_category(_drone('C0', 200, has_camera=False), _pilot(), _flight())
        assert result.category == 'open_a1'
        assert any('Flyer ID not required' in n for n in result.legal_notes)

    def test_open_category_not_legally_required(self):
        result = determine_category(_drone('C0', 249), _pilot(), _flight())
        assert result.is_legal_ra_required is False
        assert any('not a legal requirement' in n for n in result.legal_notes)


# ══════════════════════════════════════════════════════════════════
# Open A2 — C2 + A2 CofC / Legacy < 2kg + A2 CofC
# ══════════════════════════════════════════════════════════════════

class TestOpenA2:
    def test_c2_920g_a2_normal_speed(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True),
            _flight(speed_mode='normal'),
        )
        assert result.category == 'open_a2'
        assert result.min_distance_people_m == 30

    def test_c2_920g_a2_low_speed(self):
        result = determine_category(
            _drone('C2', 920, has_low_speed_mode=True),
            _pilot(has_a2_cofc=True),
            _flight(speed_mode='low_speed'),
        )
        assert result.category == 'open_a2'
        assert result.min_distance_people_m == 5

    def test_c2_low_speed_without_mode_30m(self):
        """C2 in low_speed flight but drone has no low-speed mode => 30m."""
        result = determine_category(
            _drone('C2', 920, has_low_speed_mode=False),
            _pilot(has_a2_cofc=True),
            _flight(speed_mode='low_speed'),
        )
        assert result.category == 'open_a2'
        assert result.min_distance_people_m == 30

    def test_c2_3999g_a2(self):
        result = determine_category(
            _drone('C2', 3999),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.category == 'open_a2'

    def test_legacy_1500g_a2(self):
        result = determine_category(
            _drone('legacy', 1500),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.category == 'open_a2'
        assert result.min_distance_people_m == 50

    def test_legacy_1999g_a2(self):
        result = determine_category(
            _drone('legacy', 1999),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.category == 'open_a2'
        assert result.min_distance_people_m == 50
        assert any('No low-speed reduction' in n for n in result.legal_notes)

    def test_a2_has_assessment_section(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert 'a2_assessment' in result.required_sections

    def test_a2_cannot_overfly_people(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.can_overfly_people is False


# ══════════════════════════════════════════════════════════════════
# Open A3 — fallback
# ══════════════════════════════════════════════════════════════════

class TestOpenA3:
    def test_legacy_500g_no_a2(self):
        result = determine_category(
            _drone('legacy', 500),
            _pilot(has_a2_cofc=False),
            _flight(),
        )
        assert result.category == 'open_a3'
        assert result.min_distance_people_m == 50
        assert result.min_distance_buildings_m == 50

    def test_c2_no_a2_cofc(self):
        """C2 without A2 CofC falls to A3 with warning."""
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=False),
            _flight(),
        )
        assert result.category == 'open_a3'
        assert any('A2 Certificate' in w for w in result.warnings)

    def test_legacy_under_2kg_no_a2_warning(self):
        result = determine_category(
            _drone('legacy', 1500),
            _pilot(has_a2_cofc=False),
            _flight(),
        )
        assert result.category == 'open_a3'
        assert any('A2 Certificate' in w for w in result.warnings)

    def test_c3_drone(self):
        result = determine_category(
            _drone('C3', 3000),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_c4_drone(self):
        result = determine_category(
            _drone('C4', 5000),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_legacy_24kg(self):
        result = determine_category(
            _drone('legacy', 24000),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_a3_has_assessment_section(self):
        result = determine_category(
            _drone('C3', 3000),
            _pilot(),
            _flight(),
        )
        assert 'a3_assessment' in result.required_sections

    def test_a3_150m_residential_note(self):
        result = determine_category(
            _drone('C3', 3000),
            _pilot(),
            _flight(),
        )
        assert any('150m' in n for n in result.legal_notes)

    def test_a3_urban_under_50m_buildings_blocker(self):
        result = determine_category(
            _drone('C3', 3000),
            _pilot(),
            _flight(environment_type='urban', proximity_to_buildings='under_50m'),
        )
        assert any('50m from buildings' in b for b in result.blockers)


# ══════════════════════════════════════════════════════════════════
# Specific Category
# ══════════════════════════════════════════════════════════════════

class TestSpecific:
    def test_bvlos_triggers_specific(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True, oa_type='PDRA_01'),
            _flight(vlos_type='bvlos'),
        )
        assert result.category == 'specific_pdra01'
        assert result.is_legal_ra_required is True

    def test_extended_vlos_triggers_specific(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(oa_type='FULL_SORA'),
            _flight(vlos_type='extended_vlos'),
        )
        assert result.category == 'specific_sora'

    def test_over_crowds_triggers_specific(self):
        result = determine_category(
            _drone('C1', 720),
            _pilot(oa_type='PDRA_01'),
            _flight(proximity_to_people='over_crowds'),
        )
        assert result.category == 'specific_pdra01'

    def test_controlled_airspace_triggers_specific(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(oa_type='FULL_SORA'),
            _flight(airspace_type='controlled'),
        )
        assert result.category == 'specific_sora'

    def test_restricted_airspace_triggers_specific(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(oa_type='FULL_SORA'),
            _flight(airspace_type='restricted'),
        )
        assert result.category == 'specific_sora'

    def test_danger_airspace_triggers_specific(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(oa_type='FULL_SORA'),
            _flight(airspace_type='danger'),
        )
        assert result.category == 'specific_sora'

    def test_no_oa_blocker(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True),
            _flight(vlos_type='bvlos'),
        )
        assert any('Operational Authorisation' in b for b in result.blockers)

    def test_specific_no_insurance_blocker(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(has_a2_cofc=True, oa_type='PDRA_01', has_insurance=False),
            _flight(vlos_type='bvlos'),
        )
        assert any('insurance' in b.lower() for b in result.blockers)

    def test_specific_has_ops_section(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(oa_type='PDRA_01'),
            _flight(vlos_type='bvlos'),
        )
        assert 'specific_ops' in result.required_sections

    def test_specific_no_gvc_warning(self):
        result = determine_category(
            _drone('C2', 920),
            _pilot(oa_type='PDRA_01'),
            _flight(vlos_type='bvlos'),
        )
        assert any('GVC' in w for w in result.warnings)


# ══════════════════════════════════════════════════════════════════
# Certified (>= 25kg)
# ══════════════════════════════════════════════════════════════════

class TestCertified:
    def test_25kg_drone(self):
        result = determine_category(
            _drone('legacy', 25000),
            _pilot(),
            _flight(),
        )
        assert result.category == 'certified'
        assert len(result.blockers) > 0

    def test_30kg_drone(self):
        result = determine_category(
            _drone('legacy', 30000),
            _pilot(),
            _flight(),
        )
        assert result.category == 'certified'


# ══════════════════════════════════════════════════════════════════
# Night Flying
# ══════════════════════════════════════════════════════════════════

class TestNightFlying:
    def test_night_no_green_light_blocker(self):
        result = determine_category(
            _drone('C0', 249, green_light_type='none'),
            _pilot(),
            _flight(time_of_day='night'),
        )
        assert any('green' in b.lower() for b in result.blockers)

    def test_twilight_no_green_light_blocker(self):
        result = determine_category(
            _drone('C0', 249, green_light_type='none'),
            _pilot(),
            _flight(time_of_day='twilight'),
        )
        assert any('green' in b.lower() for b in result.blockers)

    def test_night_with_built_in_light_ok(self):
        result = determine_category(
            _drone('C0', 249, green_light_type='built_in'),
            _pilot(),
            _flight(time_of_day='night'),
        )
        assert not any('green' in b.lower() for b in result.blockers)
        assert 'night_flying' in result.required_sections

    def test_night_with_external_light_ok(self):
        result = determine_category(
            _drone('C0', 200, green_light_type='external', green_light_weight_grams=30),
            _pilot(),
            _flight(time_of_day='night'),
        )
        assert not any('green' in b.lower() for b in result.blockers)

    def test_external_light_crosses_threshold_warning(self):
        """External light pushes 240g drone over 250g threshold."""
        result = determine_category(
            _drone('C0', 240, green_light_type='external', green_light_weight_grams=20),
            _pilot(),
            _flight(time_of_day='night'),
        )
        assert any('threshold' in w.lower() for w in result.warnings)

    def test_day_flight_no_night_section(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(),
            _flight(time_of_day='day'),
        )
        assert 'night_flying' not in result.required_sections


# ══════════════════════════════════════════════════════════════════
# Registration Requirements
# ══════════════════════════════════════════════════════════════════

class TestRegistration:
    def test_250g_plus_needs_flyer_id(self):
        reqs = get_registration_requirements(_drone('C1', 720))
        assert reqs['flyer_id'] is True
        assert reqs['operator_id'] is True

    def test_sub250_with_camera_needs_ids(self):
        reqs = get_registration_requirements(_drone('C0', 200, has_camera=True))
        assert reqs['flyer_id'] is True
        assert reqs['operator_id'] is True

    def test_sub100_no_camera(self):
        reqs = get_registration_requirements(_drone('C0', 90, has_camera=False))
        assert reqs['flyer_id'] is False
        assert reqs['operator_id'] is False

    def test_remote_id_warning_when_not_capable(self):
        result = determine_category(
            _drone('C0', 249, remote_id_capable=False),
            _pilot(),
            _flight(),
        )
        assert any('Remote ID' in w for w in result.warnings)


# ══════════════════════════════════════════════════════════════════
# Proximity Validation
# ══════════════════════════════════════════════════════════════════

class TestProximity:
    def test_open_over_crowds_blocker(self):
        """Over crowds never allowed in Open."""
        result = determine_category(
            _drone('C0', 249),
            _pilot(),
            _flight(proximity_to_people='over_crowds'),
        )
        # Should be pushed to Specific due to over_crowds
        assert result.category.startswith('specific')

    def test_standalone_proximity_validation(self):
        blockers = validate_proximity('open_a3', '50m_plus', 'urban', 'under_50m')
        assert any('50m from buildings' in b for b in blockers)

    def test_standalone_crowds_blocker(self):
        blockers = validate_proximity('open_a1', 'over_crowds', 'open_countryside', 'over_150m')
        assert any('assemblies' in b.lower() for b in blockers)


# ══════════════════════════════════════════════════════════════════
# FRZ Warning
# ══════════════════════════════════════════════════════════════════

class TestFRZ:
    def test_frz_warning_in_open(self):
        result = determine_category(
            _drone('C0', 249),
            _pilot(),
            _flight(airspace_type='frz'),
        )
        assert result.category == 'open_a1'  # FRZ doesn't force Specific
        assert any('FRZ' in w or 'Restriction Zone' in w for w in result.warnings)


# ══════════════════════════════════════════════════════════════════
# Edge Cases
# ══════════════════════════════════════════════════════════════════

class TestEdgeCases:
    def test_legacy_exactly_250g(self):
        """Legacy at exactly 250g is NOT sub-250g, should be A3."""
        result = determine_category(
            _drone('legacy', 250),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_legacy_exactly_2000g_with_a2(self):
        """Legacy at exactly 2000g is NOT < 2kg for A2."""
        result = determine_category(
            _drone('legacy', 2000),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_c2_exactly_4000g_with_a2(self):
        """C2 at exactly 4000g is NOT < 4kg for A2."""
        result = determine_category(
            _drone('C2', 4000),
            _pilot(has_a2_cofc=True),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_c1_at_900g(self):
        """C1 at exactly 900g is NOT < 900g for A1."""
        result = determine_category(
            _drone('C1', 900),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a3'

    def test_zero_weight_legacy(self):
        result = determine_category(
            _drone('legacy', 0),
            _pilot(),
            _flight(),
        )
        assert result.category == 'open_a1'

    def test_multiple_specific_triggers(self):
        """BVLOS + controlled airspace + over crowds = specific with multiple reasons."""
        result = determine_category(
            _drone('C2', 920),
            _pilot(oa_type='FULL_SORA'),
            _flight(vlos_type='bvlos', airspace_type='controlled', proximity_to_people='over_crowds'),
        )
        assert result.category == 'specific_sora'
        # Should have multiple reasons in legal notes
        assert len([n for n in result.legal_notes if 'requires Specific' in n]) >= 2

    def test_night_helper(self):
        info = get_night_requirements(
            _drone('C0', 249, green_light_type='none'),
            _flight(time_of_day='night'),
        )
        assert info['needs_green_light'] is True
        assert info['has_green_light'] is False
        assert len(info['blockers']) > 0

    def test_day_helper(self):
        info = get_night_requirements(
            _drone('C0', 249),
            _flight(time_of_day='day'),
        )
        assert info['needs_green_light'] is False
