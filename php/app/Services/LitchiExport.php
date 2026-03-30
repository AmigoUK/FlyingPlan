<?php

namespace App\Services;

/**
 * Litchi CSV export for third-party flight app compatibility.
 * Ported from services/litchi_export.py.
 */
class LitchiExport
{
    public static function generateLitchiCsv(array $waypoints): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $headers = [
            'latitude', 'longitude', 'altitude(m)', 'heading(deg)',
            'curvesize(m)', 'rotationdir', 'gimbalmode',
            'gimbalpitchangle', 'actiontype1', 'actionparam1',
            'altitudemode', 'speed(m/s)', 'poi_latitude',
            'poi_longitude', 'poi_altitude(m)', 'poi_altitudemode',
            'photo_timeinterval', 'photo_distinterval',
        ];

        $lines = [implode(',', $headers)];

        foreach ($waypoints as $w) {
            $heading = $w['heading_deg'] ?? 0;
            $gimbalPitch = $w['gimbal_pitch_deg'] ?? -90;

            // Gimbal mode: 0=disabled, 1=focus_poi, 2=interpolate
            $gimbalMode = 2;
            if (!empty($w['poi_lat']) && !empty($w['poi_lng'])) {
                $gimbalMode = 1;
            }

            // Map action types
            $actionType = -1;
            $actionParam = 0;
            $at = $w['action_type'] ?? null;
            if ($at === 'takePhoto') {
                $actionType = 1;
            } elseif ($at === 'startRecord') {
                $actionType = 2;
            } elseif ($at === 'stopRecord') {
                $actionType = 3;
            }

            // Curve size from turn mode
            $curveSize = 0;
            $turnMode = $w['turn_mode'] ?? '';
            if (str_contains($turnMode, 'Pass')) {
                $curveSize = 5;
            }

            $lines[] = implode(',', [
                sprintf('%.7f', $w['lat']),
                sprintf('%.7f', $w['lng']),
                sprintf('%.1f', $w['altitude_m'] ?? 30),
                sprintf('%.1f', $heading),
                (string) $curveSize,
                '0',
                (string) $gimbalMode,
                sprintf('%.1f', $gimbalPitch),
                (string) $actionType,
                (string) $actionParam,
                '1',
                sprintf('%.1f', $w['speed_ms'] ?? 5),
                !empty($w['poi_lat']) ? sprintf('%.7f', $w['poi_lat']) : '0',
                !empty($w['poi_lng']) ? sprintf('%.7f', $w['poi_lng']) : '0',
                '0',
                '0',
                '-1',
                '-1',
            ]);
        }

        return implode("\n", $lines);
    }
}
