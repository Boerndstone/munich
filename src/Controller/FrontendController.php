<?php

namespace App\Controller;

use App\Entity\Area;
use App\Entity\Rock;
use App\Entity\Contact;
use App\Dto\RockImprovementSuggestion;
use App\Service\FooterAreas;
use App\Service\FrontendCacheService;
use App\Service\RouteGroupingService;
use App\Form\ContactFormType;
use App\Form\RockImprovementSuggestionType;
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
    // Static routes must register before `/{slug}`; use `priority` so `/en` is not captured as an area slug.
    #[Route('/neuesteRouten', name: 'neuesteRouten', defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/en/latest-routes', name: 'neuesteRouten_en', defaults: ['_locale' => 'en'], priority: 350)]
    public function neuesteRouten(
        RoutesRepository $routesRepository,
    ): Response {

        $getDate = date("Y");
        $calculateDate = $getDate - 2;
        $latestRoutes = $routesRepository->latestRoutesPage($calculateDate);

        return $this->render('frontend/neuesteRouten.html.twig', [
            'latestRoutes' => $latestRoutes,
        ]);
    }

    #[Route('/Database', name: 'databasequeries', defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/en/database', name: 'databasequeries_en', defaults: ['_locale' => 'en'], priority: 350)]
    public function databasequeries(
        AreaRepository $areaRepository,
        RockRepository $rockRepository,
    ): Response {

        $dummyData = $areaRepository->sidebarNavigation();

        return $this->render('frontend/database-queries.html.twig', [
            'dummyData' => $dummyData,
        ]);
    }

    #[Route(path: '/', name: 'index', defaults: ['_locale' => 'de'], priority: 100)]
    #[Route(path: '/en', name: 'index_en', defaults: ['_locale' => 'en'], priority: 100)]
    public function index(
        FrontendCacheService $frontendCacheService,
        Request $request,
        TranslatorInterface $translator
    ): Response {

        // Use cached data for better performance
        $latestRoutes = $frontendCacheService->getLatestRoutes();
        $latestComments = $frontendCacheService->getLatestComments();
        $banned = $frontendCacheService->getBannedRocks();
        $searchTerm = $request->query->get('q');

        $response = $this->render('frontend/index.html.twig', [
            'latestRoutes' => $latestRoutes,
            'latestComments' => $latestComments,
            'banned' => $banned,
        ]);

        // Enable HTTP caching - page can be cached for 5 minutes (300 seconds),
        // and stale content can be served for up to 1 additional minute while revalidating
        $response->setSharedMaxAge(300);
        $response->headers->set('Cache-Control', 'public, s-maxage=300, stale-while-revalidate=60');

        return $response;
    }

    #[Route('/{slug}', name: 'show_rocks', defaults: ['_locale' => 'de'], priority: -20)]
    #[Route('/en/{slug}', name: 'show_rocks_en', defaults: ['_locale' => 'en'], priority: 200)]
    public function showRocksArea(
        FrontendCacheService $frontendCacheService,
        #[MapEntity(
            mapping: ['slug' => 'slug'],
            message: 'Die Seite konnte nicht gefunden werden.',
        )]
        Area $area,
        string $slug
    ): Response {

        $areaName = $area->getName();
        $areaSlug = $area->getSlug();
        $areaLat = $area->getLat();
        $areaLng = $area->getLng();
        $areaZoom = $area->getZoom();
        $areaRailwayStation = $area->getRailwayStation();
        $areaImage = $area->getHeaderImage();
        $areaKletterkonzeption = $area->getKletterkonzeption();

        // Use cached data for better performance
        $rocks = $frontendCacheService->getRocksForArea($slug);
        $routeGrades = $frontendCacheService->getRouteGradesForArea($slug);
        $top100Routes = $frontendCacheService->getTop100RoutesForArea($area->getId());

        // Group route grades by rock slug
        $gradesByRock = [];
        foreach ($routeGrades as $gradeData) {
            $rockSlug = $gradeData['rockSlug'];
            if (!isset($gradesByRock[$rockSlug])) {
                $gradesByRock[$rockSlug] = [];
            }
            $gradesByRock[$rockSlug][] = $gradeData['gradeNo'];
        }

        // Add route grades to each rock
        foreach ($rocks as &$rock) {
            $rockSlug = $rock['rockSlug'];
            $rock['routeGrades'] = isset($gradesByRock[$rockSlug]) ? implode(',', $gradesByRock[$rockSlug]) : '';
        }
        unset($rock);

        $response = $this->render('frontend/rocks.html.twig', [
            'areaName' => $areaName,
            'areaSlug' => $areaSlug,
            'areaLat' => $areaLat,
            'areaLng' => $areaLng,
            'areaZoom' => $areaZoom,
            'areaRailwayStation' => $areaRailwayStation,
            'areaImage' => $areaImage,
            'areaKletterkonzeption' => $areaKletterkonzeption,
            'rocks' => $rocks,
            'top100Routes' => $top100Routes,
        ]);

        // Enable HTTP caching - page can be cached for 5 minutes
        $response->setSharedMaxAge(300);
        $response->headers->set('Cache-Control', 'public, s-maxage=300, stale-while-revalidate=60');

        return $response;
    }

    // #[Route('/{areaSlug}/{slug}', name: 'show_rock')]

    #[Route(path: '/{areaSlug}/{slug}', name: 'show_rock', defaults: ['_locale' => 'de'], priority: 10, requirements: ['areaSlug' => '^(?!en$)[^/]++$'])]
    #[Route(path: '/en/{areaSlug}/{slug}', name: 'show_rock_en', defaults: ['_locale' => 'en'], priority: 250)]
    public function showRock(
        RoutesRepository $routesRepository,
        RockRepository $rockRepository,
        TopoRepository $topoRepository,
        PhotosRepository $photosRepository,
        RouteGroupingService $routeGroupingService,
        #[MapEntity(
            expr: 'repository.findOneByAreaSlugAndRockSlug(areaSlug, slug)',
            message: 'Die Seite konnte nicht gefunden werden.',
        )]
        Rock $rock,
        $areaSlug,
        $slug,
        Packages $assetPackages,
        Request $request,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {

        $rockId = $rockRepository->getRockId($slug);

        $topos = $topoRepository->getTopos($rockId);

        $rockName = $rock->getSlug();
        $rockLng = $rock->getLng() !== null ? $rock->getLng() : null;
        $rockLat = $rock->getLat() !== null ? $rock->getLat() : null;
        $rockPreviewImage = $rock->getPreviewImage() !== null ? $rock->getPreviewImage() : null;
        $areaName = $rock->getArea();

        // $rockDescription = $rock->getDescription();

        $routes = $rockRepository->getRoutesTopo($slug);
        $rocks = $rockRepository->getRockInformation($slug);
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

        // Group routes by topo using the service
        $groupedRoutes = $routeGroupingService->groupRoutesByTopo($routes);

        $galleryItems = $photosRepository->findPhotosForRock($rockId);

        // Serialize data to JSON format
        $jsonData = [];
        foreach ($galleryItems as $item) {
            $extension = pathinfo($item->getName(), PATHINFO_EXTENSION);
            $filenameWithoutExtension = pathinfo($item->getName(), PATHINFO_FILENAME);
            $newName = $filenameWithoutExtension . "@2x." . $extension;
            $newName3x = $filenameWithoutExtension . "@3x." . $extension;
            $thumbName = $filenameWithoutExtension . "_thumb." . $extension;
            $jsonData[] = [
                'src' =>
                $assetPackages->getUrl('https://www.munichclimbs.de/uploads/galerie/' . $item->getName()),
                'subHtml' => $item->getDescription(),
                'srcset' => 'https://www.munichclimbs.de/uploads/galerie/' . $newName . ' 2x, https://www.munichclimbs.de/uploads/galerie/' . $newName3x . ' 3x',
                'thumb' => 'https://www.munichclimbs.de/uploads/galerie/' . $thumbName
            ];
        }

        if ($rock->getOnline() == 0) {
            throw $this->createNotFoundException('The rock does not exist');
        }

        $suggestion = new RockImprovementSuggestion();
        $suggestion->rockName = $rock->getName();
        $improvementForm = $this->createForm(RockImprovementSuggestionType::class, $suggestion);
        $improvementForm->handleRequest($request);
        if ($improvementForm->isSubmitted() && $improvementForm->isValid()) {
            /** @var RockImprovementSuggestion $data */
            $data = $improvementForm->getData();
            $email = (new TemplatedEmail())
                ->from('noreply@munichclimbs.de')
                ->to('admin@munichclimbs.de')
                ->subject('munichclimbs: Aktualisierung für Fels „' . $data->rockName . '“')
                ->htmlTemplate('emails/rock_improvement.html.twig')
                ->context([
                    'name' => $data->name,
                    'contactEmail' => $data->email,
                    'rockName' => $data->rockName,
                    'routeName' => $data->routeName,
                    'grade' => $data->grade,
                    'firstAscent' => $data->firstAscent,
                    'comment' => $data->comment,
                ]);
            if (\is_string($data->email) && '' !== $data->email) {
                $email->replyTo($data->email);
            }
            $mailer->send($email);
            $this->addFlash('success', $translator->trans('rock_improvement.success'));
            $rockRoute = $request->getLocale() === 'en' ? 'show_rock_en' : 'show_rock';

            return $this->redirectToRoute($rockRoute, ['areaSlug' => $areaSlug, 'slug' => $slug]);
        }

        return $this->render('frontend/rock.html.twig', [
            'areaName' => $areaName,
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
            'groupedRoutes' => $groupedRoutes,
            'routesRepository' => $routesRepository,
            'topos' => $topos,
            'jsonData' => $jsonData,
            'locale' => $locale,
            'improvementForm' => $improvementForm,
        ]);
    }
}
