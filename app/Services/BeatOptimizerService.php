<?php

namespace App\Services;

use Illuminate\Support\Collection;

class BeatOptimizerService
{
    public function __construct(protected GeoFenceService $geo) {}

    /**
     * Reorder planned visits using a Nearest Neighbor TSP heuristic based on outlet GPS coordinates.
     *
     * @param  Collection  $visits   PlannedVisit models with 'outlet' relationship loaded (lat/lng).
     * @param  float|null  $startLat Starting latitude (defaults to centroid of all outlet coordinates).
     * @param  float|null  $startLng Starting longitude.
     * @return array  ['visits' => Collection (reordered), 'total_distance_metres' => float]
     */
    public function optimize(Collection $visits, ?float $startLat = null, ?float $startLng = null): array
    {
        $withCoords = $visits->filter(fn ($v) => $v->outlet?->lat && $v->outlet?->lng)->values();
        $withoutCoords = $visits->filter(fn ($v) => !($v->outlet?->lat && $v->outlet?->lng))->values();

        if ($withCoords->isEmpty()) {
            return ['visits' => $visits, 'total_distance_metres' => 0.0];
        }

        // Default start: centroid of all outlet coordinates
        if ($startLat === null || $startLng === null) {
            $startLat = $withCoords->avg(fn ($v) => (float) $v->outlet->lat);
            $startLng = $withCoords->avg(fn ($v) => (float) $v->outlet->lng);
        }

        $remaining = $withCoords->all();
        $ordered = [];
        $currentLat = $startLat;
        $currentLng = $startLng;
        $totalDistance = 0.0;

        while (!empty($remaining)) {
            $nearest = null;
            $nearestDist = PHP_FLOAT_MAX;
            $nearestKey = null;

            foreach ($remaining as $key => $visit) {
                $dist = $this->geo->haversineDistanceMetres(
                    $currentLat,
                    $currentLng,
                    (float) $visit->outlet->lat,
                    (float) $visit->outlet->lng
                );
                if ($dist < $nearestDist) {
                    $nearestDist = $dist;
                    $nearest = $visit;
                    $nearestKey = $key;
                }
            }

            $totalDistance += $nearestDist;
            $ordered[] = $nearest;
            $currentLat = (float) $nearest->outlet->lat;
            $currentLng = (float) $nearest->outlet->lng;
            unset($remaining[$nearestKey]);
        }

        $result = collect(array_merge($ordered, $withoutCoords->all()));

        return [
            'visits' => $result,
            'total_distance_metres' => $totalDistance,
        ];
    }
}
