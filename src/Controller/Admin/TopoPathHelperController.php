<?php

namespace App\Controller\Admin;

use App\Entity\Topo;
use App\Repository\TopoRepository;
use App\Service\TopoPathRendererService;
use App\Service\TopoSvgParser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TopoPathHelperController extends AbstractDashboardController
{
    public function __construct(
        private TopoRepository $topoRepository,
        private TopoPathRendererService $pathRenderer,
        private TopoSvgParser $topoSvgParser,
    ) {
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/admin/topo-path-helper', name: 'admin_topo_path_helper')]
    public function index(): Response
    {
        return $this->render('admin/topo_path_helper.html.twig', [
            'topoEdit' => null,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/admin/topo/{id}/edit-paths', name: 'admin_topo_edit_paths', requirements: ['id' => '\d+'])]
    public function editPaths(int $id): Response
    {
        $topo = $this->topoRepository->find($id);
        if (!$topo instanceof Topo) {
            throw $this->createNotFoundException('Topo not found.');
        }

        $pathConfigs = $this->pathRenderer->decodePathsForTopo(
            $topo->getPathCollection(),
            $topo->getPath()
        );
        $pathsJson = $pathConfigs !== null ? json_encode($pathConfigs, \JSON_UNESCAPED_SLASHES) : '[]';

        $viewBox = $topo->getViewBox() ?? '0 0 1024 820';
        $parts = preg_split('/\s+/', trim($viewBox), 4);
        $viewBoxW = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : 1024;
        $viewBoxH = isset($parts[3]) && is_numeric($parts[3]) ? (int) $parts[3] : 820;

        $imageUrl = '';
        if ($topo->getImage() !== null && $topo->getImage() !== '') {
            $srcset = $this->topoSvgParser->buildTopoImageSrcset($topo->getImage());
            $imageUrl = $srcset['src'] ?? ('https://www.munichclimbs.de/build/images/topos/' . $topo->getImage() . '.webp');
        }

        // Do not pass pathsOverlaySvg to path-helper — it can contain broken content if pathCollection was saved as JS/SVG. Use pathsJson only.
        $topoEdit = [
            'id' => $topo->getId(),
            'name' => $topo->getName(),
            'imageUrl' => $imageUrl,
            'viewBoxW' => $viewBoxW,
            'viewBoxH' => $viewBoxH,
            'pathsJson' => $pathsJson,
            'pathsOverlaySvg' => '', // step 0 overlay is built client-side from pathsJson only
            'saveUrl' => $this->generateUrl('admin_topo_save_paths', ['id' => $topo->getId()]),
            'backUrl' => $this->container->get(AdminUrlGenerator::class)->setController(TopoCrudController::class)->setAction('edit')->setEntityId($topo->getId())->generateUrl(),
        ];

        $topoEditJson = json_encode($topoEdit, \JSON_UNESCAPED_SLASHES);
        if ($topoEditJson === false) {
            $topoEditJson = 'null';
        }
        $topoEditJsonBase64 = base64_encode($topoEditJson);

        return $this->render('admin/topo_path_helper.html.twig', [
            'topoEdit' => $topoEdit,
            'topoEditJsonBase64' => $topoEditJsonBase64,
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/admin/topo/{id}/save-paths', name: 'admin_topo_save_paths', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function savePaths(int $id, Request $request): Response
    {
        $topo = $this->topoRepository->find($id);
        if (!$topo instanceof Topo) {
            return $this->json(['success' => false, 'error' => 'Topo not found.'], 404);
        }

        $phpLiteral = $request->request->get('phpLiteral');
        $viewBox = $request->request->get('viewBox');
        if (!\is_string($phpLiteral)) {
            return $this->json(['success' => false, 'error' => 'Missing phpLiteral.'], 400);
        }

        $topo->setPathCollection(trim($phpLiteral));
        if (\is_string($viewBox) && $viewBox !== '') {
            $topo->setViewBox(trim($viewBox));
        }
        $topo->setPath(null); // prefer pathCollection after edit
        $this->topoRepository->getEntityManager()->flush();

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $editUrl = $adminUrlGenerator->setController(TopoCrudController::class)->setAction('edit')->setEntityId($topo->getId())->generateUrl();

        return $this->json(['success' => true, 'redirectUrl' => $editUrl]);
    }
}
