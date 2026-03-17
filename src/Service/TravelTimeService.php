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
        private bool $osrmSslVerify = true,
    ) {
    }

    /**
     * Test connectivity to OSRM. Returns null on success, or an error message string.
     */
    public function testConnection(): ?string
    {
        $url = self::OSRM_BASE . '/' . self::MUNICH_LNG . ',' . self::MUNICH_LAT . ';11.6,48.2?overview=false';

        if (\extension_loaded('curl')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return 'cURL init failed';
            }
            $opts = [
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
                \CURLOPT_USERAGENT => 'munichclimbs/1.0',
                \CURLOPT_SSL_VERIFYPEER => $this->osrmSslVerify,
                \CURLOPT_SSL_VERIFYHOST => $this->osrmSslVerify ? 2 : 0,
            ];
            if (\defined('CURL_SSLVERSION_TLSv1_2')) {
                $opts[\CURLOPT_SSLVERSION] = \CURL_SSLVERSION_TLSv1_2;
            }
            curl_setopt_array($ch, $opts);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($errno !== 0) {
                return sprintf('cURL error %d: %s', $errno, $error ?: 'Unknown');
            }
            if ($httpCode < 200 || $httpCode >= 300) {
                return sprintf('Unexpected HTTP status %d from OSRM', $httpCode);
            }
            if (!\is_string($body) || $body === '') {
                return 'Empty response body from OSRM';
            }
            $data = json_decode($body, true);
            if (!\is_array($data) || !isset($data['code']) || $data['code'] !== 'Ok') {
                return 'OSRM response not OK or invalid JSON';
            }

            return null;
        }

        $context = stream_context_create([
            'http' => ['timeout' => self::HTTP_TIMEOUT],
            'ssl' => ['verify_peer' => $this->osrmSslVerify],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $e = error_get_last();
            return $e['message'] ?? 'file_get_contents failed (check allow_url_fopen and outbound HTTPS)';
        }

        $httpCode = null;
        if (isset($http_response_header[0]) && \preg_match('~^HTTP/\S+\s+(\d{3})~', $http_response_header[0], $m)) {
            $httpCode = (int) $m[1];
        }
        if ($httpCode !== null && ($httpCode < 200 || $httpCode >= 300)) {
            return sprintf('Unexpected HTTP status %d from OSRM', $httpCode);
        }

        if ($response === '') {
            return 'Empty response body from OSRM';
        }
        $data = json_decode($response, true);
        if (!\is_array($data) || !isset($data['code']) || $data['code'] !== 'Ok') {
            return 'OSRM response not OK or invalid JSON';
        }

        return null;
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

        $response = $this->fetchUrl($url);

        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);

        if (!\is_array($data) || ($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0]['duration'])) {
            return null;
        }

        $durationSeconds = (float) $data['routes'][0]['duration'];

        return (int) round($durationSeconds / 60);
    }

    /**
     * Fetch URL via cURL (preferred on shared hosts) or file_get_contents.
     */
    private function fetchUrl(string $url): ?string
    {
        if (\extension_loaded('curl')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            $opts = [
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
                \CURLOPT_USERAGENT => 'munichclimbs/1.0',
                \CURLOPT_SSL_VERIFYPEER => $this->osrmSslVerify,
                \CURLOPT_SSL_VERIFYHOST => $this->osrmSslVerify ? 2 : 0,
            ];
            if (\defined('CURL_SSLVERSION_TLSv1_2')) {
                $opts[\CURLOPT_SSLVERSION] = \CURL_SSLVERSION_TLSv1_2;
            }
            curl_setopt_array($ch, $opts);
            $response = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            if ($errno !== 0 || $response === false) {
                return null;
            }

            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => self::HTTP_TIMEOUT,
                'header' => "User-Agent: munichclimbs/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => $this->osrmSslVerify,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response !== false ? $response : null;
    }

    private function formatCoord(float $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
