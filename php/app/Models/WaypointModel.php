<?php

namespace App\Models;

use CodeIgniter\Model;

class WaypointModel extends Model
{
    protected $table = 'waypoints';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'flight_plan_id', 'index', 'lat', 'lng', 'altitude_m', 'speed_ms',
        'heading_deg', 'gimbal_pitch_deg', 'turn_mode', 'turn_damping_dist',
        'hover_time_s', 'action_type', 'poi_lat', 'poi_lng',
    ];

    /**
     * Convert a waypoint row to an associative array.
     */
    public static function toArray(object $wp): array
    {
        return [
            'index'            => (int) $wp->index,
            'lat'              => (float) $wp->lat,
            'lng'              => (float) $wp->lng,
            'altitude_m'       => (float) ($wp->altitude_m ?? 30.0),
            'speed_ms'         => (float) ($wp->speed_ms ?? 5.0),
            'heading_deg'      => isset($wp->heading_deg) ? (float) $wp->heading_deg : null,
            'gimbal_pitch_deg' => (float) ($wp->gimbal_pitch_deg ?? -90.0),
            'turn_mode'        => $wp->turn_mode ?? 'toPointAndStopWithDiscontinuityCurvature',
            'turn_damping_dist' => (float) ($wp->turn_damping_dist ?? 0.0),
            'hover_time_s'     => (float) ($wp->hover_time_s ?? 0.0),
            'action_type'      => $wp->action_type ?? null,
            'poi_lat'          => isset($wp->poi_lat) ? (float) $wp->poi_lat : null,
            'poi_lng'          => isset($wp->poi_lng) ? (float) $wp->poi_lng : null,
        ];
    }
}
