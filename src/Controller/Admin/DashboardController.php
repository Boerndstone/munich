<?php

namespace App\Controller\Admin;

use App\Entity\Area;
use App\Entity\Rock;
use App\Entity\Topo;
use App\Entity\User;
use App\Entity\Photos;
use App\Entity\Routes;
use App\Entity\Videos;
use App\Entity\ToDoListe;
use App\Entity\ClimbedRoutes;
use App\Entity\Comment;
use App\Repository\AreaRepository;
use App\Repository\RockRepository;
use App\Service\AreasService;
use App\Service\FrontendCacheService;
use Symfony\UX\Chartjs\Model\Chart;
use App\Repository\RoutesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use Symfony\Component\HttpFoundation\JsonResponse;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;


class DashboardController extends AbstractDashboardController
{
    private RoutesRepository $routesRepository;
    private AreaRepository $areaRepository;
    private RockRepository $rockRepository;
    private ChartBuilderInterface $chartBuilder;
    private RequestStack $requestStack;
    private AreasService $areasService;
    private FrontendCacheService $frontendCacheService;

    public function __construct(
        RoutesRepository $routesRepository,
        AreaRepository $areaRepository,
        RockRepository $rockRepository,
        ChartBuilderInterface $chartBuilder,
        RequestStack $requestStack,
        AreasService $areasService,
        FrontendCacheService $frontendCacheService
    ) {
        $this->areaRepository = $areaRepository;
        $this->rockRepository = $rockRepository;
        $this->routesRepository = $routesRepository;
        $this->chartBuilder = $chartBuilder;
        $this->requestStack = $requestStack;
        $this->areasService = $areasService;
        $this->frontendCacheService = $frontendCacheService;
    }

    // Have to to make user in db + user form!!!
    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        $areas = $this->areaRepository->findAllAreasAlphabetical();
        $getRoutes = $this->routesRepository->getAllRoutes();
        $getAreas = $this->areaRepository->getAllAreas();
        $getRocks = $this->rockRepository->getAllRocks();

        $request = $this->requestStack->getCurrentRequest();
        $areaId = $request->query->get('areaId');
        if ($areaId !== null) {
            $area = $this->areaRepository->find($areaId);
            $climbedRoutesInArea = $this->routesRepository->findClimbedRoutesByArea($area);
        } else {
            $climbedRoutesInArea = $this->routesRepository->findAllClimbedRoutes();
        }


        return $this->render('admin/index.html.twig', [
            'chart' => $this->createChart(),
            'chartBernd' => $this->createChartBernd(),
            'areas' => $areas,
            'getAreas' => $getAreas,
            'getRocks' => $getRocks,
            'getRoutes' => $getRoutes,
            'climbedRoutesInArea' => $climbedRoutesInArea,
            'climbedRoutesCount' => count($climbedRoutesInArea),
        ]);
    }

    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    #[Route('/climbed-routes-count/{areaId}', name: 'climbed_routes_count')]
    public function climbedRoutesCount(int $areaId): Response
    {
        $area = $this->areaRepository->find($areaId);
        $climbedRoutesInArea = $this->routesRepository->findClimbedRoutesByArea($area);

        return new JsonResponse(['count' => count($climbedRoutesInArea)]);
    }

    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    #[Route('/update-grades', name: 'update_grades')]
    public function updateGrades(): Response
    {
        $this->routesRepository->updateGrades();

        return new JsonResponse(['status' => 'success']);
    }

    #[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
    #[Route('/admin/clear-frontend-cache', name: 'admin_clear_frontend_cache')]
    public function clearFrontendCache(): Response
    {
        // Clear all frontend caches
        $this->areasService->clearCache();
        $this->frontendCacheService->clearCache();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Frontend cache cleared successfully'
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Munichclimbs')
            ->setFaviconPath('build/images/favicon/favicon.png');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToCrud('To Do Liste', 'fa fa-tags', ToDoListe::class)->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToCrud('Gebiete', 'fa fa-tags', Area::class)->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToCrud('Felsen', 'fa fa-home', Rock::class);
        yield MenuItem::linkToCrud('Touren', 'fa fa-home', Routes::class);
        yield MenuItem::linkToCrud('Touren geklettert', 'fa fa-home', ClimbedRoutes::class);
        yield MenuItem::linkToCrud('Topos', 'fa fa-home', Topo::class)->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToCrud('Photos', 'fa fa-camera-retro', Photos::class)->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToCrud('Kommentare', 'fa fa-comment', Comment::class)->setPermission('ROLE_MODERATOR');
        yield MenuItem::linkToCrud('Videos', 'fa fa-video', Videos::class);
        yield MenuItem::linkToCrud('User', 'fa fa-user', User::class)->setPermission('ROLE_SUPER_ADMIN');
        yield MenuItem::linkToRoute('Geolocation', 'fa fa-map-marker', 'admin_geolocation')->setPermission('ROLE_SUPER_ADMIN');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setAvatarUrl($user->getAvatarUrl() ?? '');
    }

    // This is the setup for a global Show Action
    // It is possible to diable it for specific pages or disable it globally
    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureAssets(): Assets
    {
        $assets = parent::configureAssets();
        $assets->addWebpackEncoreEntry('admin', 'admin');

        return $assets;
    }

    private function createChart(): Chart
    {
        $belowSix = $this->routesRepository->findAllRoutesBelowSix();
        $belowEight = $this->routesRepository->findAllRoutesBelowEight();
        $greaterEight = $this->routesRepository->findAllRoutesGreaterEight();
        $projects = $this->routesRepository->findAllProjectds();

        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => ['1 - 5', '6 - 7', '8 - 11', 'Projekte'],
            'datasets' => [
                [
                    'label' => 'Schwierigkeiten',
                    'backgroundColor' => ['#15803d', '#075985', '#b91c1c', 'black'],
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [$belowSix, $belowEight, $greaterEight, $projects],
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 2500,
                ],
            ],
        ]);

        return $chart;
    }

    private function createChartBernd(): Chart
    {
        $alreadyClimbed = $this->routesRepository->findAllAlreadyClimbed();
        $allRoutes = $this->routesRepository->getAllRoutes();

        $chartBernd = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chartBernd->setData([
            'labels' => ['Routen gesamt', 'Bisher geklettert'],
            'datasets' => [
                [
                    'label' => 'Schwierigkeiten',
                    'backgroundColor' => ['#b91c1c', '#15803d'],
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => [$allRoutes, $alreadyClimbed],
                ],
            ],
        ]);
        $chartBernd->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 2500,
                ],
            ],
        ]);

        return $chartBernd;
    }
}
