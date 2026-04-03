<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\RockRepository;
use App\Service\RockAccessService;
use App\Service\RouteTopoChoiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RouteTopoChoicesController extends AbstractController
{
    #[Route('/admin/routes/topos-for-rock/{rockId}', name: 'admin_routes_topos_for_rock', requirements: ['rockId' => '\\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function __invoke(
        int $rockId,
        RockRepository $rockRepository,
        RouteTopoChoiceService $routeTopoChoiceService,
        RockAccessService $rockAccessService,
    ): JsonResponse {
        $rock = $rockRepository->find($rockId);
        if ($rock === null) {
            return new JsonResponse(['error' => 'not_found'], 404);
        }

        if (!$rockAccessService->canEditRock($this->getUser(), $rock)) {
            return new JsonResponse(['error' => 'forbidden'], 403);
        }

        $choices = $routeTopoChoiceService->choicesForRock($rock);
        $topos = [];
        foreach ($choices as $label => $value) {
            $topos[] = ['label' => $label, 'value' => $value];
        }

        return new JsonResponse(['topos' => $topos]);
    }
}
