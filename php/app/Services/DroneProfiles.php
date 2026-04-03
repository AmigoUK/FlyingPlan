<?php

namespace App\Services;

/**
 * DJI drone profiles with camera specs and flight limits.
 * Ported from services/drone_profiles.py.
 */
class DroneProfiles
{
    public const DEFAULT_DRONE = 'mini_4_pro';

    public const PROFILES = [
        'mini_4_pro' => [
            'display_name'      => 'DJI Mini 4 Pro',
            'droneEnumValue'    => 77,
            'droneSubEnumValue' => 0,
            'payloadEnumValue'  => 66,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 16.0,
            'max_wind_speed_ms' => 10.7,
            'max_flight_time_min' => 34,
            'sensor_width_mm'   => 9.7,
            'sensor_height_mm'  => 7.3,
            'focal_length_mm'   => 6.7,
            'image_width_px'    => 4032,
            'image_height_px'   => 3024,
        ],
        'mini_5_pro' => [
            'display_name'      => 'DJI Mini 5 Pro',
            'droneEnumValue'    => 77,
            'droneSubEnumValue' => 2,
            'payloadEnumValue'  => 66,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 16.0,
            'max_wind_speed_ms' => 10.7,
            'max_flight_time_min' => 34,
            'sensor_width_mm'   => 9.7,
            'sensor_height_mm'  => 7.3,
            'focal_length_mm'   => 6.7,
            'image_width_px'    => 4032,
            'image_height_px'   => 3024,
        ],
        'mavic_3' => [
            'display_name'      => 'DJI Mavic 3',
            'droneEnumValue'    => 60,
            'droneSubEnumValue' => 0,
            'payloadEnumValue'  => 52,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 21.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 46,
            'sensor_width_mm'   => 17.3,
            'sensor_height_mm'  => 13.0,
            'focal_length_mm'   => 12.29,
            'image_width_px'    => 5280,
            'image_height_px'   => 3956,
        ],
        'mavic_3_pro' => [
            'display_name'      => 'DJI Mavic 3 Pro',
            'droneEnumValue'    => 77,
            'droneSubEnumValue' => 1,
            'payloadEnumValue'  => 67,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 21.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 43,
            'sensor_width_mm'   => 17.3,
            'sensor_height_mm'  => 13.0,
            'focal_length_mm'   => 12.29,
            'image_width_px'    => 5280,
            'image_height_px'   => 3956,
        ],
        'mavic_3_classic' => [
            'display_name'      => 'DJI Mavic 3 Classic',
            'droneEnumValue'    => 60,
            'droneSubEnumValue' => 2,
            'payloadEnumValue'  => 52,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 21.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 46,
            'sensor_width_mm'   => 17.3,
            'sensor_height_mm'  => 13.0,
            'focal_length_mm'   => 12.29,
            'image_width_px'    => 5280,
            'image_height_px'   => 3956,
        ],
        'mavic_4_pro' => [
            'display_name'      => 'DJI Mavic 4 Pro',
            'droneEnumValue'    => 60,
            'droneSubEnumValue' => 3,
            'payloadEnumValue'  => 52,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 21.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 46,
            'sensor_width_mm'   => 17.3,
            'sensor_height_mm'  => 13.0,
            'focal_length_mm'   => 12.29,
            'image_width_px'    => 5280,
            'image_height_px'   => 3956,
        ],
        'air_3' => [
            'display_name'      => 'DJI Air 3',
            'droneEnumValue'    => 67,
            'droneSubEnumValue' => 0,
            'payloadEnumValue'  => 61,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 19.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 46,
            'sensor_width_mm'   => 9.7,
            'sensor_height_mm'  => 7.3,
            'focal_length_mm'   => 6.7,
            'image_width_px'    => 4032,
            'image_height_px'   => 3024,
        ],
        'air_3s' => [
            'display_name'      => 'DJI Air 3S',
            'droneEnumValue'    => 67,
            'droneSubEnumValue' => 1,
            'payloadEnumValue'  => 61,
            'max_altitude_m'    => 120,
            'max_speed_ms'      => 19.0,
            'max_wind_speed_ms' => 12.0,
            'max_flight_time_min' => 46,
            'sensor_width_mm'   => 13.2,
            'sensor_height_mm'  => 8.8,
            'focal_length_mm'   => 5.1,
            'image_width_px'    => 8064,
            'image_height_px'   => 6048,
        ],
    ];

    /**
     * Get a drone profile by model key, with fallback to default.
     */
    public static function getProfile(string $droneModel): array
    {
        return self::PROFILES[$droneModel] ?? self::PROFILES[self::DEFAULT_DRONE];
    }

    /**
     * Get dropdown choices: [[value, display_name], ...].
     */
    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::PROFILES as $key => $profile) {
            $choices[] = [$key, $profile['display_name']];
        }
        return $choices;
    }
}
