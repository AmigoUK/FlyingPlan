<?php

namespace App\Models;

use CodeIgniter\Model;

class RiskAssessmentModel extends Model
{
    protected $table = 'risk_assessments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'order_id', 'pilot_id',
        'site_ground_hazards', 'site_obstacles_mapped', 'site_50m_separation',
        'site_150m_residential', 'airspace_frz_checked', 'airspace_restricted_checked',
        'airspace_notams_reviewed', 'airspace_max_altitude_confirmed',
        'airspace_planned_altitude', 'weather_acceptable', 'weather_wind_speed',
        'weather_wind_direction', 'weather_visibility', 'weather_precipitation',
        'weather_temperature', 'equip_condition_ok', 'equip_battery_adequate',
        'equip_battery_level', 'equip_propellers_ok', 'equip_gps_lock',
        'equip_remote_ok', 'equip_remote_id_active',
        'imsafe_illness', 'imsafe_medication', 'imsafe_stress',
        'imsafe_alcohol', 'imsafe_fatigue', 'imsafe_eating',
        'perms_flyer_id_valid', 'perms_operator_id_displayed',
        'perms_insurance_valid', 'perms_authorizations_checked',
        'emergency_landing_site', 'emergency_contacts_confirmed',
        'emergency_contingency_plan',
        'operational_category', 'category_version',
        'night_green_light_fitted', 'night_green_light_on',
        'night_vlos_maintainable', 'night_orientation_visible',
        'a2_distance_confirmed', 'a2_low_speed_active', 'a2_segregation_assessed',
        'a3_150m_from_areas', 'a3_50m_from_people', 'a3_50m_from_buildings',
        'specific_ops_manual_reviewed', 'specific_insurance_confirmed',
        'specific_oa_valid',
        'risk_level', 'decision', 'mitigation_notes', 'pilot_declaration',
        'gps_latitude', 'gps_longitude',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    public const CHECK_FIELDS = [
        'site_ground_hazards', 'site_obstacles_mapped', 'site_50m_separation',
        'site_150m_residential', 'airspace_frz_checked', 'airspace_restricted_checked',
        'airspace_notams_reviewed', 'airspace_max_altitude_confirmed',
        'weather_acceptable', 'equip_condition_ok', 'equip_battery_adequate',
        'equip_propellers_ok', 'equip_gps_lock', 'equip_remote_ok',
        'equip_remote_id_active', 'imsafe_illness', 'imsafe_medication',
        'imsafe_stress', 'imsafe_alcohol', 'imsafe_fatigue', 'imsafe_eating',
        'perms_flyer_id_valid', 'perms_operator_id_displayed',
        'perms_insurance_valid', 'perms_authorizations_checked',
        'emergency_landing_site', 'emergency_contacts_confirmed',
        'emergency_contingency_plan',
    ];

    public const NIGHT_CHECK_FIELDS = [
        'night_green_light_fitted', 'night_green_light_on',
        'night_vlos_maintainable', 'night_orientation_visible',
    ];

    public const A2_CHECK_FIELDS = [
        'a2_distance_confirmed', 'a2_low_speed_active', 'a2_segregation_assessed',
    ];

    public const A3_CHECK_FIELDS = [
        'a3_150m_from_areas', 'a3_50m_from_people', 'a3_50m_from_buildings',
    ];

    public const SPECIFIC_CHECK_FIELDS = [
        'specific_ops_manual_reviewed', 'specific_insurance_confirmed', 'specific_oa_valid',
    ];

    public const CATEGORY_CHECKS = [
        'open_a2'         => self::A2_CHECK_FIELDS,
        'open_a3'         => self::A3_CHECK_FIELDS,
        'specific_pdra01' => self::SPECIFIC_CHECK_FIELDS,
        'specific_sora'   => self::SPECIFIC_CHECK_FIELDS,
    ];

    /**
     * Check if all mandatory checks are passed.
     */
    public static function allChecksPassed(object $ra): bool
    {
        foreach (self::CHECK_FIELDS as $field) {
            if (empty($ra->$field)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all required checks for this assessment's category.
     */
    public static function getRequiredChecks(object $ra): array
    {
        $checks = self::CHECK_FIELDS;
        $category = $ra->operational_category ?? '';

        if (in_array($category, ['open_a2', 'open_a3', 'specific_pdra01', 'specific_sora'])) {
            $checks = array_merge($checks, self::CATEGORY_CHECKS[$category]);
        }

        // Night checks if time_of_day is night
        // (caller must pass order context for this)

        return $checks;
    }
}
