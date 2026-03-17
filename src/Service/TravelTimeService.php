<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Calculates driving duration from Munich to a destination using OSRM.
 * Results are cached per destination to respect OSRM demo server rate limits.
 * Uses PHP stream context for HTTP (no Symfony HttpClient dependency).
 */
class TravelTimeService
{
    private const MUNICH_LNG = 11.5820;
    private const MUNICH_LAT = 48.1351;
    private const OSRM_BASE = 'https://router.project-osrm.org/route/v1/driving';
    private const CACHE_TTL = 604800; // 7 days
    private const HTTP_TIMEOUT = 5;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    /**
     * Get driving duration from Munich to the given point, in minutes.
     * Returns null if coords are missing or the routing request fails.
     */
    public function getDrivingMinutesFromMunich(?float $toLng, ?float $toLat): ?int
    {
        if ($toLng === null || $toLat === null || ($toLng == 0 && $toLat == 0)) {
            return null;
        }

        $cacheKey = sprintf('travel_munich_%s_%s', $this->formatCoord($toLat), $this->formatCoord($toLng));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($toLng, $toLat): ?int {
            $result = $this->fetchFromOsrm((float) $toLng, (float) $toLat);
            // Retry failed lookups sooner (1 hour), keep successful ones 7 days
            $item->expiresAfter($result !== null ? self::CACHE_TTL : 3600);

            return $result;
        });
    }

    private function fetchFromOsrm(float $toLng, float $toLat): ?int
    {
        $coords = sprintf('%f,%f;%f,%f', self::MUNICH_LNG, self::MUNICH_LAT, $toLng, $toLat);
        $url = self::OSRM_BASE . '/' . $coords . '?overview=false';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::HTTP_TIMEOUT,
                'header' => "User-Agent: munichclimbs/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        try {
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            if (!\is_array($data) || ($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0]['duration'])) {
                return null;
            }

            $durationSeconds = (float) $data['routes'][0]['duration'];

            return (int) round($durationSeconds / 60);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatCoord(float $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
