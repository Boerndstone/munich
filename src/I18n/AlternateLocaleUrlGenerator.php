<?php

declare(strict_types=1);

namespace App\I18n;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the URL for the same logical page in the other locale (DE/EN route pairs).
 * If the current route has no pair, returns null (caller may fall back to home in target locale).
 */
final class AlternateLocaleUrlGenerator
{
    /**
     * @var array<string, array{de: string, en: string}>
     */
    private const ROUTE_PAIRS = [
        'index' => ['de' => 'index', 'en' => 'index_en'],
        'index_en' => ['de' => 'index', 'en' => 'index_en'],
        'show_rock' => ['de' => 'show_rock', 'en' => 'show_rock_en'],
        'show_rock_en' => ['de' => 'show_rock', 'en' => 'show_rock_en'],
        'show_rocks' => ['de' => 'show_rocks', 'en' => 'show_rocks_en'],
        'show_rocks_en' => ['de' => 'show_rocks', 'en' => 'show_rocks_en'],
        'neuesteRouten' => ['de' => 'neuesteRouten', 'en' => 'neuesteRouten_en'],
        'neuesteRouten_en' => ['de' => 'neuesteRouten', 'en' => 'neuesteRouten_en'],
        'databasequeries' => ['de' => 'databasequeries', 'en' => 'databasequeries_en'],
        'databasequeries_en' => ['de' => 'databasequeries', 'en' => 'databasequeries_en'],
        'datenschutz' => ['de' => 'datenschutz', 'en' => 'datenschutz_en'],
        'datenschutz_en' => ['de' => 'datenschutz', 'en' => 'datenschutz_en'],
        'impressum' => ['de' => 'impressum', 'en' => 'impressum_en'],
        'impressum_en' => ['de' => 'impressum', 'en' => 'impressum_en'],
        'free_climbing_grade_comparison' => ['de' => 'free_climbing_grade_comparison', 'en' => 'free_climbing_grade_comparison_en'],
        'free_climbing_grade_comparison_en' => ['de' => 'free_climbing_grade_comparison', 'en' => 'free_climbing_grade_comparison_en'],
        'bouldering_grade_comparison_redirect' => ['de' => 'bouldering_grade_comparison_redirect', 'en' => 'bouldering_grade_comparison_en_redirect'],
        'bouldering_grade_comparison_en_redirect' => ['de' => 'bouldering_grade_comparison_redirect', 'en' => 'bouldering_grade_comparison_en_redirect'],
        'app_login' => ['de' => 'app_login', 'en' => 'app_login_en'],
        'app_login_en' => ['de' => 'app_login', 'en' => 'app_login_en'],
        'upload_photo' => ['de' => 'upload_photo', 'en' => 'upload_photo_en'],
        'upload_photo_de' => ['de' => 'upload_photo_de', 'en' => 'upload_photo_en'],
        'upload_photo_en' => ['de' => 'upload_photo', 'en' => 'upload_photo_en'],
        'frontend_topo_path_suggestion' => ['de' => 'frontend_topo_path_suggestion', 'en' => 'frontend_topo_path_suggestion_en'],
        'frontend_topo_path_suggestion_en' => ['de' => 'frontend_topo_path_suggestion', 'en' => 'frontend_topo_path_suggestion_en'],
        'frontend_topo_path_suggestion_routes' => ['de' => 'frontend_topo_path_suggestion_routes', 'en' => 'frontend_topo_path_suggestion_routes_en'],
        'frontend_topo_path_suggestion_routes_en' => ['de' => 'frontend_topo_path_suggestion_routes', 'en' => 'frontend_topo_path_suggestion_routes_en'],
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generateForLocale(string $targetLocale): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        // Language switch is frontend-only; admin stays DE-only.
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return null;
        }

        $route = (string) $request->attributes->get('_route');
        if ('' === $route || str_starts_with($route, '_')) {
            return null;
        }

        $localeKey = 'en' === $targetLocale ? 'en' : 'de';
        if (!isset(self::ROUTE_PAIRS[$route][$localeKey])) {
            return null;
        }

        $targetRoute = self::ROUTE_PAIRS[$route][$localeKey];
        $params = $this->extractRouteParams($request);
        if (\in_array($route, [
            'frontend_topo_path_suggestion',
            'frontend_topo_path_suggestion_en',
            'frontend_topo_path_suggestion_routes',
            'frontend_topo_path_suggestion_routes_en',
        ], true)) {
            foreach (['rock', 'topoNr'] as $q) {
                if ($request->query->has($q)) {
                    $params[$q] = $request->query->get($q);
                }
            }
        }

        try {
            return $this->urlGenerator->generate($targetRoute, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    private function extractRouteParams(Request $request): array
    {
        $params = $request->attributes->get('_route_params');
        if (!\is_array($params)) {
            $out = [];
            foreach (['slug', 'areaSlug'] as $key) {
                if (!$request->attributes->has($key)) {
                    continue;
                }
                $value = $request->attributes->get($key);
                if (\is_scalar($value) || null === $value) {
                    $out[$key] = $value;
                }
            }

            return $out;
        }

        $out = [];
        foreach ($params as $key => $value) {
            if ('_locale' === $key) {
                continue;
            }
            if (\is_scalar($value) || null === $value) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
