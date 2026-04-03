<?php

namespace App\Services;

/**
 * Geographic utility functions for coordinate transformations.
 * Ported from services/geo_utils.py.
 */
class GeoUtils
{
    private const EARTH_RADIUS = 6371000.0; // metres

    /**
     * Equirectangular projection: lat/lng to local metres.
     */
    public static function toMetres(float $lat, float $lng, float $centerLat, float $centerLng): array
    {
        $x = deg2rad($lng - $centerLng) * cos(deg2rad($centerLat)) * self::EARTH_RADIUS;
        $y = deg2rad($lat - $centerLat) * self::EARTH_RADIUS;
        return [$x, $y];
    }

    /**
     * Convert local metres back to lat/lng.
     */
    public static function toLatLng(float $x, float $y, float $centerLat, float $centerLng): array
    {
        $lat = $centerLat + rad2deg($y / self::EARTH_RADIUS);
        $lng = $centerLng + rad2deg($x / (self::EARTH_RADIUS * cos(deg2rad($centerLat))));
        return [$lat, $lng];
    }

    /**
     * Rotate a point around the origin.
     */
    public static function rotate(float $x, float $y, float $angleRad): array
    {
        $cos = cos($angleRad);
        $sin = sin($angleRad);
        return [$x * $cos - $y * $sin, $x * $sin + $y * $cos];
    }

    /**
     * Calculate a point at distance/bearing from origin.
     */
    public static function offsetPoint(float $lat, float $lng, float $distanceM, float $bearingRad): array
    {
        $dLat = $distanceM * cos($bearingRad) / self::EARTH_RADIUS;
        $dLng = $distanceM * sin($bearingRad) / (self::EARTH_RADIUS * cos(deg2rad($lat)));
        return [$lat + rad2deg($dLat), $lng + rad2deg($dLng)];
    }

    /**
     * Calculate initial bearing from one point to another (0-360 degrees).
     */
    public static function headingTo(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $dLng = deg2rad($toLng - $fromLng);
        $lat1 = deg2rad($fromLat);
        $lat2 = deg2rad($toLat);
        $x = sin($dLng) * cos($lat2);
        $y = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLng);
        $bearing = rad2deg(atan2($x, $y));
        return fmod($bearing + 360.0, 360.0);
    }

    /**
     * Great-circle distance in metres (Haversine formula).
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return self::EARTH_RADIUS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Calculate polygon centroid from [[lat, lng], ...] array.
     */
    public static function polygonCentroid(array $coords): array
    {
        if (empty($coords)) {
            return [0.0, 0.0];
        }
        $sumLat = 0.0;
        $sumLng = 0.0;
        foreach ($coords as $c) {
            $sumLat += $c[0];
            $sumLng += $c[1];
        }
        $n = count($coords);
        return [$sumLat / $n, $sumLng / $n];
    }
}
