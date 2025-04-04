<?php

namespace App\Controller;

use App\Entity\Area;
use App\Entity\Rock;
use App\Entity\Contact;
use App\Service\FooterAreas;
use App\Form\ContactFormType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use App\Repository\AreaRepository;
use App\Repository\RockRepository;
use App\Repository\TopoRepository;
use App\Repository\PhotosRepository;
use App\Repository\RoutesRepository;
use App\Repository\CommentRepository;
use Symfony\Component\Asset\Packages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;


class FrontendController extends AbstractController
{
    // I have to define the static routes before the dynamic routes with $slug otherwise I get a 404 for the static routes!!!
    #[Route('/neuesteRouten', name: 'neuesteRouten')]
    public function neuesteRouten(
        AreaRepository $areaRepository,
        RoutesRepository $routesRepository,
        FooterAreas $footerAreas,
    ): Response {

        $areas = $footerAreas->getFooterAreas();

        $getDate = date("Y");
        $calculateDate = $getDate - 2;
        $latestRoutes = $routesRepository->latestRoutesPage($calculateDate);

        $sideBar = $areaRepository->sidebarNavigation();

        return $this->render('frontend/neuesteRouten.html.twig', [
            'areas' => $areas,
            'latestRoutes' => $latestRoutes,
            'sideBar' => $sideBar,
        ]);
    }

    #[Route('/Database', name: 'databasequeries')]
    public function databasequeries(
        AreaRepository $areaRepository,
        RockRepository $rockRepository,
    ): Response {

        $dummyData = $areaRepository->sidebarNavigation();

        return $this->render('frontend/database-queries.html.twig', [
            'dummyData' => $dummyData,
        ]);
    }

    #[Route(
        path: '/',
        name: 'index',
        defaults: ['_locale' => 'de'],
        requirements: ['_locale' => 'de']
    )]
    #[Route(
        path: '/en',
        name: 'index_en',
        defaults: ['_locale' => 'en'],
        requirements: ['_locale' => 'en']
    )]
    public function index(
        AreaRepository $areaRepository,
        RockRepository $rockRepository,
        RoutesRepository $routesRepository,
        CommentRepository $commentRepository,
        Request $request,
        TranslatorInterface $translator
    ): Response {

        $latestRoutes = $routesRepository->latestRoutes();
        $latestComments = $commentRepository->latestComments();
        $banned = $rockRepository->saisonalGesperrt();
        $areas = $areaRepository->getAreasInformation();
        $sideBar = $areaRepository->sidebarNavigation();
        $searchTerm = $request->query->get('q');
        $areaRepository->search($searchTerm);
        //dd($searchRoutes);

        return $this->render('frontend/index.html.twig', [
            'areas' => $areas,
            'latestRoutes' => $latestRoutes,
            'latestComments' => $latestComments,
            'banned' => $banned,
            'sideBar' => $sideBar,
        ]);
    }

    #[Route('/{slug}', name: 'show_rocks')]
    public function showRocksArea(
        AreaRepository $areaRepository,
        RockRepository $rockRepository,
        #[MapEntity] Area $area,
        string $slug,
        FooterAreas $footerAreas
    ): Response {

        $areas = $footerAreas->getFooterAreas();
        $areaName = $area->getName();
        $areaSlug = $area->getSlug();
        $areaLat = $area->getLat();
        $areaLng = $area->getLng();
        $areaZoom = $area->getZoom();
        $areaRailwayStation = $area->getRailwayStation();
        $areaImage = $area->getHeaderImage();

        $sideBar = $areaRepository->sidebarNavigation();
        $rocks = $rockRepository->getRocksInformation($slug);


        return $this->render('frontend/rocks.html.twig', [
            'areas' => $areas,
            'areaName' => $areaName,
            'areaSlug' => $areaSlug,
            'areaLat' => $areaLat,
            'areaLng' => $areaLng,
            'areaZoom' => $areaZoom,
            'areaRailwayStation' => $areaRailwayStation,
            'areaImage' => $areaImage,
            'rocks' => $rocks,
            'sideBar' => $sideBar,
        ]);
    }

    // #[Route('/{areaSlug}/{slug}', name: 'show_rock')]

    #[Route(
        path: '/{areaSlug}/{slug}',
        name: 'show_rock',
        defaults: ['_locale' => 'de'],
        requirements: ['_locale' => 'de']
    )]
    #[Route(
        path: '/en/{areaSlug}/{slug}',
        name: 'show_rock_en',
        defaults: ['_locale' => 'en'],
        requirements: ['_locale' => 'en']
    )]
    public function showRock(
        AreaRepository $areaRepository,
        RoutesRepository $routesRepository,
        RockRepository $rockRepository,
        TopoRepository $topoRepository,
        PhotosRepository $photosRepository,
        #[MapEntity] Rock $rock,
        $areaSlug,
        $slug,
        FooterAreas $footerAreas,
        Packages $assetPackages,
        Request $request
    ): Response {

        $rockId = $rockRepository->getRockId($slug);

        $topos = $topoRepository->getTopos($rockId);

        $rockName = $rock->getSlug();
        $rockLng = $rock->getLng() !== null ? $rock->getLng() : null;
        $rockLat = $rock->getLat() !== null ? $rock->getLat() : null;
        $rockPreviewImage = $rock->getPreviewImage() !== null ? $rock->getPreviewImage() : null;
        $areaName = $rock->getArea();

        // $rockDescription = $rock->getDescription();

        $rocks = $rockRepository->getRockInformation($slug);
        $routes = $rockRepository->getRoutesTopo($slug);
        $comments = $rockRepository->getCommentsForRoutes($slug);

        $locale = $request->getLocale();
        $rockDescription = $rockRepository->findWithTranslations($slug, $locale);
        $rockDescriptionArray = [
            'description' => $rockDescription[0]['description'] ?? null,
            'access' => $rockDescription[0]['access'] ?? null,
            'nature' => $rockDescription[0]['nature'] ?? null,
            'flowers' => $rockDescription[0]['flowers'] ?? null,
        ];

        $hasTranslationDescription = $rockRepository->hasTranslationDescription($slug, $locale);

        foreach ($routes as &$route) {
            $route['routeComment'] = [];
            foreach ($comments as $comment) {
                if ($comment['routeId'] === $route['routeId']) {
                    $route['routeComment'][]
                        = [
                            'comment' => $comment['routeComment'],
                            'username' => $comment['username'],
                            'date' => $comment['date'],
                        ];
                }
            }
        }

        $galleryItems = $photosRepository->findPhotosForRock($rockId);

        // Serialize data to JSON format
        $jsonData = [];
        foreach ($galleryItems as $item) {
            $extension = pathinfo($item->getName(), PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($item->getName(), PATHINFO_FILENAME);
            $newName = $filenameWithoutExtension . "@2x." . $extension;
            $thumbName = $filenameWithoutExtension . "_thumb." . $extension;
            $jsonData[] = [
                'src' =>
                $assetPackages->getUrl('https://www.munichclimbs.de/uploads/galerie/' . $item->getName()),
                'subHtml' => $item->getDescription(),
                'srcset' => 'https://www.munichclimbs.de/uploads/galerie/' . $newName,
                'thumb' => 'https://www.munichclimbs.de/uploads/galerie/' . $thumbName
            ];
        }

        $sideBar = $areaRepository->sidebarNavigation();

        $areas = $footerAreas->getFooterAreas();

        if ($rock->getOnline() == 0) {
            throw $this->createNotFoundException('The rock does not exist');
        }

        return $this->render('frontend/rock.html.twig', [
            'areaName' => $areaName,
            'areas' => $areas,
            'slug' => $slug,
            'areaSlug' => $areaSlug,
            'rocks' => $rocks,
            'rockLng' => $rockLng,
            'rockLat' => $rockLat,
            'rockPreviewImage' => $rockPreviewImage,
            'rockName' => $rockName,
            'hasTranslationDescription' => $hasTranslationDescription,
            'description' => $rockDescriptionArray['description'],
            'access' => $rockDescriptionArray['access'],
            'nature' => $rockDescriptionArray['nature'],
            'flowers' => $rockDescriptionArray['flowers'],
            'routes' => $routes,
            'routesRepository' => $routesRepository,
            'topos' => $topos,
            'sideBar' => $sideBar,
            'jsonData' => $jsonData,
            'locale' => $locale,
        ]);
    }
}
