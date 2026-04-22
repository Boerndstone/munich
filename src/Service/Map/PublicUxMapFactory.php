<?php

declare(strict_types=1);

namespace App\Service\Map;

use App\Service\AreasService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polyline;
use Twig\Environment;

/**
 * Builds {@see Map} instances for public UX Leaflet maps (index, area overview, rock topo).
 */
final class PublicUxMapFactory
{
    private const INDEX_CENTER = [48.74, 12.44];

    private const INDEX_ZOOM = 7.0;

    public function __construct(
        private readonly Environment $twig,
        private readonly AreasService $areasService,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    public function createIndexOverviewMap(): Map
    {
        $markers = [];
        $markerMeta = [];

        foreach ($this->areasService->getAreasInformation() as $area) {
            $lat = isset($area['lat']) ? (float) $area['lat'] : null;
            $lng = isset($area['lng']) ? (float) $area['lng'] : null;
            if (null === $lat || null === $lng) {
                continue;
            }

            $popupHtml = $this->twig->render('map/popup_index_area.html.twig', [
                'area' => $area,
                'areaUrl' => $this->router->generate('show_rocks', ['slug' => $area['slug']]),
            ]);

            $travel = $area['travelTimeMinutes'] ?? null;
            $travelNum = null !== $travel && is_numeric($travel) ? (float) $travel : null;

            $markers[] = new Marker(
                position: new Point($lat, $lng),
                infoWindow: new InfoWindow(content: $popupHtml),
                extra: [
                    'travelTimeMinutes' => $travelNum,
                ],
            );
            $markerMeta[] = [
                'travelTimeMinutes' => $travelNum,
            ];
        }

        $map = new Map(
            center: new Point(self::INDEX_CENTER[0], self::INDEX_CENTER[1]),
            zoom: self::INDEX_ZOOM,
            markers: $markers,
        );

        return $map->extra([
            'markerMeta' => $markerMeta,
        ]);
    }

    /**
     * @param array<string, mixed> $railwayStation decoded JSON from Area (trainStations / campingSites)
     * @param list<array<string, mixed>> $rocks rows from {@see \App\Repository\RockRepository::getRocksInformation}
     */
    public function createAreaOverviewMap(
        string $areaName,
        float $areaLat,
        float $areaLng,
        int|float $areaZoom,
        array $railwayStation,
        array $rocks,
    ): Map {
        if (!\is_array($railwayStation)) {
            $railwayStation = [];
        }

        $markers = [];
        $markerMeta = [];

        $rockImageBaseUrl = 'https://www.munichclimbs.de/build/images/rock/';

        foreach ($rocks as $rock) {
            $lat = isset($rock['rockLat']) ? (float) $rock['rockLat'] : null;
            $lng = isset($rock['rockLng']) ? (float) $rock['rockLng'] : null;
            if (null === $lat || null === $lng) {
                continue;
            }

            $popupHtml = $this->twig->render('map/popup_area_rock.html.twig', [
                'areaName' => $areaName,
                'rock' => $rock,
                'rockImageBaseUrl' => $rockImageBaseUrl,
                'rockUrl' => $this->router->generate('show_rock', [
                    'areaSlug' => $rock['areaSlug'],
                    'slug' => $rock['rockSlug'],
                ]),
            ]);

            $childFriendly = isset($rock['rockChild']) && (int) $rock['rockChild'] === 1;
            $sunny = isset($rock['rockSunny']) && true === $rock['rockSunny'];
            $rain = isset($rock['rockRain']) && true === $rock['rockRain'];
            $train = isset($rock['rockTrain']) && (int) $rock['rockTrain'] === 1;
            $bike = isset($rock['rockBike']) && (int) $rock['rockBike'] === 1;

            $markers[] = new Marker(
                position: new Point($lat, $lng),
                infoWindow: new InfoWindow(content: $popupHtml),
                extra: ['layer' => 'rock'],
            );
            $markerMeta[] = [
                'layer' => 'rock',
                'childFriendly' => $childFriendly,
                'sunny' => $sunny,
                'rain' => $rain,
                'train' => $train,
                'bike' => $bike,
            ];
        }

        foreach ($railwayStation['trainStations'] ?? [] as $trainStation) {
            if (!\is_array($trainStation)) {
                continue;
            }
            $lat = isset($trainStation['lat']) ? (float) $trainStation['lat'] : null;
            $lng = isset($trainStation['lng']) ? (float) $trainStation['lng'] : null;
            if (null === $lat || null === $lng) {
                continue;
            }
            $name = (string) ($trainStation['name'] ?? '');
            $link = isset($trainStation['link']) ? (string) $trainStation['link'] : '';
            $popupHtml = $this->twig->render('map/popup_train_station.html.twig', [
                'name' => $name,
                'link' => $link,
            ]);
            $markers[] = new Marker(
                position: new Point($lat, $lng),
                infoWindow: new InfoWindow(content: $popupHtml),
                extra: ['layer' => 'railway'],
                icon: Icon::url(self::trainStationDataUri())->width(40)->height(40),
            );
            $markerMeta[] = ['layer' => 'railway'];
        }

        foreach ($railwayStation['campingSites'] ?? [] as $campingSite) {
            if (!\is_array($campingSite)) {
                continue;
            }
            $lat = isset($campingSite['lat']) ? (float) $campingSite['lat'] : null;
            $lng = isset($campingSite['lng']) ? (float) $campingSite['lng'] : null;
            if (null === $lat || null === $lng) {
                continue;
            }
            $name = (string) ($campingSite['name'] ?? '');
            $link = isset($campingSite['link']) ? (string) $campingSite['link'] : '';
            $popupHtml = $this->twig->render('map/popup_camping_site.html.twig', [
                'name' => $name,
                'link' => $link,
            ]);
            $markers[] = new Marker(
                position: new Point($lat, $lng),
                infoWindow: new InfoWindow(content: $popupHtml),
                extra: ['layer' => 'camping'],
                icon: Icon::url(self::campingDataUri())->width(40)->height(40),
            );
            $markerMeta[] = ['layer' => 'camping'];
        }

        $map = new Map(
            center: new Point($areaLat, $areaLng),
            zoom: (float) $areaZoom,
            markers: $markers,
        );

        return $map->extra([
            'markerMeta' => $markerMeta,
        ]);
    }

    /**
     * @param array<string, mixed> $rock first row from {@see \App\Repository\RockRepository::getRockInformation}
     */
    public function createRockTopoMap(array $rock): Map
    {
        $lat = isset($rock['rockLat']) ? (float) $rock['rockLat'] : null;
        $lng = isset($rock['rockLng']) ? (float) $rock['rockLng'] : null;
        if (null === $lat || null === $lng) {
            $lat = 48.74;
            $lng = 11.95;
        }

        $zoom = isset($rock['rockZoom']) ? (float) $rock['rockZoom'] : 17.0;
        if (0.0 === $zoom) {
            $zoom = 17.0;
        }

        $rockName = (string) ($rock['rockName'] ?? '');
        $pathRaw = $rock['pathCoordinates'] ?? null;
        $geometries = $this->normalizePathCoordinates($pathRaw);

        $markers = [];
        $polylines = [];

        $parkingSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="32" viewBox="0 0 448 512"><path stroke="#fff" fill="#075985" d="M64 32C28.7 32 0 60.7 0 96v320c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64zm128 224h48c17.7 0 32-14.3 32-32s-14.3-32-32-32h-48zm48 64h-48v32c0 17.7-14.3 32-32 32s-32-14.3-32-32V168c0-22.1 17.9-40 40-40h72c53 0 96 43 96 96s-43 96-96 96"/></svg>';
        $parkingIcon = Icon::url('data:image/svg+xml;base64,'.base64_encode($parkingSvg))->width(25)->height(41);

        if ($geometries !== []) {
            $parsed = $this->parseRockGeometries($geometries);
            if (null !== $parsed['parking']) {
                $markers[] = new Marker(
                    position: $parsed['parking'],
                    icon: $parkingIcon,
                );
            }
            if ($parsed['linePoints'] !== []) {
                $polylines[] = new Polyline($parsed['linePoints']);
            }
            if (null !== $parsed['summit']) {
                $markers[] = new Marker(
                    position: $parsed['summit'],
                    infoWindow: new InfoWindow(
                        content: '<div class="main-map-popup-card"><div class="main-map-popup-body"><p class="mb-0 fw-medium">'.htmlspecialchars($rockName, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'</p></div></div>',
                        opened: true,
                    ),
                );
            }
        } else {
            $markers[] = new Marker(position: new Point($lat, $lng));
        }

        return new Map(
            center: new Point($lat, $lng),
            zoom: $zoom,
            markers: $markers,
            polylines: $polylines,
        );
    }

    private static function trainStationDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="#334155" d="M12 2c-4 0-8 .5-8 4v9.5A3.5 3.5 0 0 0 7.5 19L6 20.5v.5h2.23l2-2H14l2 2h2v-.5L16.5 19a3.5 3.5 0 0 0 3.5-3.5V6c0-3.5-3.58-4-8-4M7.5 17A1.5 1.5 0 0 1 6 15.5A1.5 1.5 0 0 1 7.5 14A1.5 1.5 0 0 1 9 15.5A1.5 1.5 0 0 1 7.5 17m3.5-7H6V6h5zm2 0V6h5v4zm3.5 7a1.5 1.5 0 0 1-1.5-1.5a1.5 1.5 0 0 1 1.5-1.5a1.5 1.5 0 0 1-1.5 1.5"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private static function campingDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"><path fill="#334155" d="M19 7h-8v7H3V5H1v15h2v-3h18v3h2v-9a4 4 0 0 0-4-4M7 13a3 3 0 0 0 3-3a3 3 0 0 0-3-3a3 3 0 0 0-3 3a3 3 0 0 0 3 3"/></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return list<array{type: string, coordinates: mixed}>
     */
    private function normalizePathCoordinates(mixed $raw): array
    {
        if (null === $raw || '' === $raw) {
            return [];
        }
        if (\is_string($raw)) {
            $decoded = json_decode($raw, true);

            return \is_array($decoded) ? $decoded : [];
        }

        return \is_array($raw) ? $raw : [];
    }

    /**
     * @param list<array{type: string, coordinates: mixed}> $geometries
     *
     * @return array{parking: ?Point, summit: ?Point, linePoints: list<Point>}
     */
    private function parseRockGeometries(array $geometries): array
    {
        $linePoints = [];
        /** @var list<Point> $points */
        $points = [];

        foreach ($geometries as $item) {
            if (!\is_array($item) || !isset($item['type'], $item['coordinates'])) {
                continue;
            }
            $type = (string) $item['type'];
            if ('Point' === $type) {
                $pt = $this->geoJsonPointToPoint($item['coordinates']);
                if (null !== $pt) {
                    $points[] = $pt;
                }
            } elseif ('LineString' === $type) {
                $coords = $item['coordinates'];
                if (!\is_array($coords)) {
                    continue;
                }
                foreach ($coords as $pair) {
                    $pt = $this->geoJsonPairToPoint($pair);
                    if (null !== $pt) {
                        $linePoints[] = $pt;
                    }
                }
            }
        }

        $parking = $points[0] ?? null;
        $summit = $points[2] ?? ($points[\count($points) - 1] ?? null);
        if ($summit === $parking && \count($points) > 1) {
            $summit = $points[\count($points) - 1];
        }

        return [
            'parking' => $parking,
            'summit' => $summit,
            'linePoints' => $linePoints,
        ];
    }

    /**
     * @param mixed $coordinates GeoJSON position [lng, lat] or nested
     */
    private function geoJsonPointToPoint(mixed $coordinates): ?Point
    {
        if (!\is_array($coordinates) || !isset($coordinates[0], $coordinates[1])) {
            return null;
        }

        return $this->geoJsonPairToPoint($coordinates);
    }

    /**
     * @param mixed $pair [lng, lat]
     */
    private function geoJsonPairToPoint(mixed $pair): ?Point
    {
        if (!\is_array($pair) || !isset($pair[0], $pair[1])) {
            return null;
        }
        $lng = (float) $pair[0];
        $lat = (float) $pair[1];

        return new Point($lat, $lng);
    }
}
