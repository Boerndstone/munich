<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Rock;

/**
 * Provides a lightweight, offline estimate for the final leg from an area's
 * configured station to a rock. It intentionally does not replace turn-by-turn routing.
 */
final class RailwayAccessEstimateService
{
    private const WALKING_KMH = 4.5;
    private const CYCLING_KMH = 13.0;
    private const ROUTE_DISTANCE_FACTOR = 1.3;

    /**
     * @return array<int, array{stationName: string, minutes: int, mode: 'walk'|'bike'}>
     */
    public function estimatesForRock(Rock $rock): array
    {
        if (!$rock->isTrain() && !$rock->isBike()) {
            return [];
        }

        $area = $rock->getArea();
        $rockLat = $this->toCoordinate($rock->getLat());
        $rockLng = $this->toCoordinate($rock->getLng());
        if ($area === null || $rockLat === null || $rockLng === null) {
            return [];
        }

        $nearestStation = null;
        $nearestDistanceKm = null;
        foreach ($this->stations($area->getRailwayStation()) as $station) {
            $stationLat = $this->toCoordinate($station['lat'] ?? null);
            $stationLng = $this->toCoordinate($station['lng'] ?? null);
            if ($stationLat === null || $stationLng === null) {
                continue;
            }

            $distanceKm = $this->distanceKm($stationLat, $stationLng, $rockLat, $rockLng);
            if ($nearestDistanceKm === null || $distanceKm < $nearestDistanceKm) {
                $nearestStation = $station;
                $nearestDistanceKm = $distanceKm;
            }
        }

        if ($nearestStation === null || $nearestDistanceKm === null) {
            return [];
        }

        $stationName = trim((string) ($nearestStation['name'] ?? 'Bahnhof')) ?: 'Bahnhof';
        $estimates = [];

        if ($rock->isTrain()) {
            $estimates[] = [
                'stationName' => $stationName,
                'minutes' => $this->estimateMinutes($nearestDistanceKm, self::WALKING_KMH),
                'mode' => 'walk',
            ];
        }

        if ($rock->isBike()) {
            $estimates[] = [
                'stationName' => $stationName,
                'minutes' => $this->estimateMinutes($nearestDistanceKm, self::CYCLING_KMH),
                'mode' => 'bike',
            ];
        }

        return $estimates;
    }

    private function estimateMinutes(float $directDistanceKm, float $speedKmh): int
    {
        $minutes = (int) (ceil((($directDistanceKm * self::ROUTE_DISTANCE_FACTOR) / $speedKmh) * 60 / 5) * 5);

        return max(5, $minutes);
    }

    /** @return array<int, array<string, mixed>> */
    private function stations(?array $railwayStations): array
    {
        if ($railwayStations === null) {
            return [];
        }

        // Support both the legacy single-station object and the current station list.
        if (array_key_exists('lat', $railwayStations) || array_key_exists('lng', $railwayStations)) {
            return [$railwayStations];
        }

        if (isset($railwayStations['trainStations']) && is_array($railwayStations['trainStations'])) {
            return array_values(array_filter($railwayStations['trainStations'], 'is_array'));
        }

        return array_values(array_filter($railwayStations, 'is_array'));
    }

    private function toCoordinate(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
