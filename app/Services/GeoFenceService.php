<?php

namespace App\Services;

/**
 * Geo-fence validation: point-in-radius (Haversine) and point-in-polygon (ray-casting).
 * Used for check-in and optional order capture validation.
 */
class GeoFenceService
{
    /** Earth radius in metres (WGS84) */
    private const EARTH_RADIUS_METRES = 6371000;

    /**
     * Check if point (lat, lng) is within radius (metres) of center (centerLat, centerLng).
     * Uses Haversine formula.
     */
    public function pointInRadius(
        float $lat,
        float $lng,
        float $centerLat,
        float $centerLng,
        int $radiusMetres
    ): bool {
        $distance = $this->haversineDistanceMetres($lat, $lng, $centerLat, $centerLng);
        return $distance <= $radiusMetres;
    }

    /**
     * Distance in metres between two points (Haversine).
     */
    public function haversineDistanceMetres(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);
        $dLat = $lat2Rad - $lat1Rad;
        $dLng = $lng2Rad - $lng1Rad;
        $a = sin($dLat / 2) ** 2 + cos($lat1Rad) * cos($lat2Rad) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return self::EARTH_RADIUS_METRES * $c;
    }

    /**
     * Check if point (lat, lng) is inside polygon.
     * Polygon: array of ['lat' => float, 'lng' => float] (closed: first point = last, or we close it).
     * Uses ray-casting (even-odd rule).
     */
    public function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $vertices = array_values($polygon);
        $n = count($vertices);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $latI = (float) ($vertices[$i]['lat'] ?? $vertices[$i][0] ?? 0);
            $lngI = (float) ($vertices[$i]['lng'] ?? $vertices[$i][1] ?? 0);
            $latJ = (float) ($vertices[$j]['lat'] ?? $vertices[$j][0] ?? 0);
            $lngJ = (float) ($vertices[$j]['lng'] ?? $vertices[$j][1] ?? 0);

            if (($lngJ - $lngI) == 0) {
                continue;
            }
            if ((($lngI <= $lng && $lng < $lngJ) || ($lngJ <= $lng && $lng < $lngI))
                && ($lat < ($latJ - $latI) * ($lng - $lngI) / ($lngJ - $lngI) + $latI)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Validate that (lat, lng) is inside the outlet's geo-fence (if any).
     * Returns [true, null] if allowed, [false, 'message'] if not.
     *
     * @param float $lat
     * @param float $lng
     * @param string|null $geoFenceType  null, 'radius', 'polygon'
     * @param float|null $centerLat       for radius
     * @param float|null $centerLng       for radius
     * @param int|null $radiusMetres      for radius
     * @param array|null $polygon         for polygon (array of {lat, lng})
     */
    public function validatePointForOutlet(
        float $lat,
        float $lng,
        ?string $geoFenceType,
        ?float $centerLat,
        ?float $centerLng,
        ?int $radiusMetres,
        ?array $polygon
    ): array {
        if (empty($geoFenceType) || $geoFenceType === 'none') {
            return [true, null];
        }

        if ($geoFenceType === 'radius') {
            if ($centerLat === null || $centerLng === null || $radiusMetres === null || $radiusMetres <= 0) {
                return [true, null]; // misconfigured: allow
            }
            if (!$this->pointInRadius($lat, $lng, $centerLat, $centerLng, $radiusMetres)) {
                $dist = (int) $this->haversineDistanceMetres($lat, $lng, $centerLat, $centerLng);
                return [false, "You are {$dist} m from the outlet. Check-in is only allowed within {$radiusMetres} m."];
            }
            return [true, null];
        }

        if ($geoFenceType === 'polygon') {
            if (empty($polygon) || !is_array($polygon)) {
                return [true, null];
            }
            if (!$this->pointInPolygon($lat, $lng, $polygon)) {
                return [false, 'You are outside the outlet area. Check-in is only allowed within the defined boundary.'];
            }
            return [true, null];
        }

        return [true, null];
    }
}
