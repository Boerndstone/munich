<?php

namespace App\Controller\Admin;

use App\Entity\Topo;
use App\Repository\TopoRepository;
use App\Service\RockAccessService;
use App\Service\TopoPathEditorPayloadFactory;
use App\Service\TopoPathEditorSaveService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TopoPathHelperController extends AbstractDashboardController
{
    public function __construct(
        private readonly TopoRepository $topoRepository,
        private readonly RockAccessService $rockAccessService,
        private readonly TopoPathEditorPayloadFactory $payloadFactory,
        private readonly TopoPathEditorSaveService $saveService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/topo-path-helper', name: 'admin_topo_path_helper')]
    public function index(): Response
    {
        return $this->render('admin/topo_path_helper.html.twig', [
            'topoEdit' => null,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/topo/{id}/edit-paths', name: 'admin_topo_edit_paths', requirements: ['id' => '\d+'])]
    public function editPaths(int $id): Response
    {
        $topo = $this->topoRepository->find($id);
        if (!$topo instanceof Topo) {
            throw $this->createNotFoundException('Topo not found.');
        }

        if (!$this->rockAccessService->canEditTopo($this->getUser(), $topo)) {
            throw $this->createAccessDeniedException();
        }

        $backUrl = $this->adminUrlGenerator->setController(TopoCrudController::class)->setAction('edit')->setEntityId($topo->getId())->generateUrl();

        $topoEdit = $this->payloadFactory->buildEditPayload(
            $topo,
            $this->generateUrl('admin_topo_save_paths', ['id' => $topo->getId()]),
            $backUrl,
        );

        $topoEditJson = json_encode($topoEdit, \JSON_UNESCAPED_SLASHES);
        $topoEditJsonBase64 = base64_encode($topoEditJson);

        return $this->render('admin/topo_path_helper.html.twig', [
            'topoEdit' => $topoEdit,
            'topoEditJsonBase64' => $topoEditJsonBase64,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/topo/{id}/save-paths', name: 'admin_topo_save_paths', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function savePaths(int $id, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid(TopoPathEditorSaveService::CSRF_INTENT, $token)) {
            return $this->json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $topo = $this->topoRepository->find($id);
        if (!$topo instanceof Topo) {
            return $this->json(['success' => false, 'error' => 'Topo not found.'], 404);
        }

        if (!$this->rockAccessService->canEditTopo($this->getUser(), $topo)) {
            return $this->json(['success' => false, 'error' => 'Forbidden.'], 403);
        }

        $phpLiteral = $request->request->get('phpLiteral');
        if (!\is_string($phpLiteral)) {
            return $this->json(['success' => false, 'error' => 'Missing phpLiteral.'], 400);
        }

        $this->saveService->savePhpLiteral($topo, $phpLiteral);

        $editUrl = $this->adminUrlGenerator->setController(TopoCrudController::class)->setAction('edit')->setEntityId($topo->getId())->generateUrl();

        return $this->json(['success' => true, 'redirectUrl' => $editUrl]);
    }
}
