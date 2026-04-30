<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Rock;
use App\Entity\Topo;
use App\Repository\RoutesRepository;

/**
 * Builds the topo path editor JSON payload (TOPO_EDIT) shared by admin, frontend tools, and Mithelfen.
 */
final class TopoPathEditorPayloadFactory
{
    public function __construct(
        private readonly RoutesRepository $routesRepository,
        private readonly TopoPathRendererService $pathRenderer,
        private readonly TopoSvgParser $topoSvgParser,
        private readonly TopoPathGradeColorService $topoPathGradeColorService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEditPayload(
        Topo $topo,
        string $saveUrl,
        string $backUrl,
        int $viewBoxW = 1024,
        int $viewBoxH = 820,
    ): array {
        $pathConfigs = $this->pathRenderer->decodePathsForTopo($topo->getPathCollection(), null);
        $pathsJson = $pathConfigs !== null ? json_encode($pathConfigs, \JSON_UNESCAPED_SLASHES) : '[]';

        $imageUrl = '';
        if ($topo->getImage() !== null && $topo->getImage() !== '') {
            $srcset = $this->topoSvgParser->buildTopoImageSrcset($topo->getImage());
            $imageUrl = $srcset['src'] ?? ('https://www.munichclimbs.de/build/images/topos/' . $topo->getImage() . '.webp');
        }

        $routesForColors = [];
        $rock = $topo->getRocks();
        if ($rock !== null) {
            $routesForColors = $this->buildRoutesForColorsForRockAndTopoNumber($rock, (int) $topo->getNumber());
        }

        return [
            'id' => $topo->getId(),
            'name' => $topo->getName(),
            'imageUrl' => $imageUrl,
            'viewBoxW' => $viewBoxW,
            'viewBoxH' => $viewBoxH,
            'pathsJson' => $pathsJson,
            'pathsOverlaySvg' => '',
            'saveUrl' => $saveUrl,
            'backUrl' => $backUrl,
            'routesForColors' => $routesForColors,
        ];
    }

    /**
     * Public Mithelfen tool: no persisted topo; submit goes to pending queue.
     *
     * @return array<string, mixed>
     */
    public function buildPublicSuggestionPayload(
        string $submitUrl,
        string $backUrl,
        ?Rock $rock,
        ?int $topoNumber,
    ): array {
        $routesForColors = [];
        if ($rock instanceof Rock && $topoNumber !== null && $topoNumber > 0) {
            $routesForColors = $this->buildRoutesForColorsForRockAndTopoNumber($rock, $topoNumber);
        }

        return [
            'id' => null,
            'name' => 'Tourenpfade (Vorschlag)',
            'imageUrl' => '',
            'viewBoxW' => 1024,
            'viewBoxH' => 820,
            'pathsJson' => '[]',
            'pathsOverlaySvg' => '',
            'saveUrl' => $submitUrl,
            'backUrl' => $backUrl,
            'routesForColors' => $routesForColors,
            'suggestionMode' => true,
        ];
    }

    /**
     * @return list<array{nr: mixed, name: string, grade: string, chartBucket: int|string|null, strokeHex: string}>
     */
    public function buildRoutesForColorsForRockAndTopoNumber(Rock $rock, int $topoNumber): array
    {
        $routeRows = $this->routesRepository->createQueryBuilder('r')
            ->where('r.rock = :rock')
            ->andWhere('r.topoId = :topoNumber')
            ->setParameter('rock', $rock)
            ->setParameter('topoNumber', $topoNumber)
            ->orderBy('r.nr', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();

        $routesForColors = [];
        foreach ($routeRows as $route) {
            $grade = $route->getGrade();
            $bucket = GradeTranslationService::uiaaChartBucketForGrade($grade);
            $routesForColors[] = [
                'nr' => $route->getNr(),
                'name' => $route->getName() ?? '',
                'grade' => $grade ?? '',
                'chartBucket' => $bucket,
                'strokeHex' => $this->topoPathGradeColorService->strokeHexForGrade($grade),
            ];
        }

        return $routesForColors;
    }
}
