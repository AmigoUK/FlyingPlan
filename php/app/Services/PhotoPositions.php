<?php

namespace App\Services;

/**
 * Photogrammetry camera position export (Pix4D/Metashape compatible).
 * Ported from services/photo_positions.py.
 */
class PhotoPositions
{
    public static function generatePhotoPositionsCsv(array $waypoints): string
    {
        usort($waypoints, fn($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        $headers = ['imageName', 'latitude', 'longitude', 'altitude_m', 'omega', 'phi', 'kappa'];
        $lines = [implode(',', $headers)];

        foreach ($waypoints as $w) {
            $gimbal = $w['gimbal_pitch_deg'] ?? -90;
            $omega = 90 + $gimbal; // -90 -> 0, -45 -> 45, 0 -> 90

            $phi = 0.0;

            $heading = $w['heading_deg'] ?? 0;
            $kappa = (float) $heading;

            $imageName = sprintf('IMG_%04d.JPG', $w['index'] ?? 0);

            $lines[] = implode(',', [
                $imageName,
                sprintf('%.7f', $w['lat']),
                sprintf('%.7f', $w['lng']),
                sprintf('%.1f', $w['altitude_m'] ?? 30),
                sprintf('%.2f', $omega),
                sprintf('%.2f', $phi),
                sprintf('%.2f', $kappa),
            ]);
        }

        return implode("\n", $lines);
    }
}
