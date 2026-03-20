<?php

namespace App\Service;

class RouteGroupingService
{
    /**
     * Groups routes by topo name for display in the template
     *
     * @param array $routes Array of route data from repository
     * @return array Grouped routes by topo name
     */
    public function groupRoutesByTopo(array $routes): array
    {
        $groupedRoutes = [];
        
        foreach ($routes as $routeData) {
            $topoName = $routeData['topoName'];
            
            if (!isset($groupedRoutes[$topoName])) {
                $groupedRoutes[$topoName] = [
                    'topoName' => $topoName,
                    'topoNumber' => $routeData['topoNumber'],
                    'topoImage' => $routeData['topoImage'] ?? null,
                    'topoPathCollection' => $routeData['topoPathCollection'] ?? null,
                    'topoPath' => $routeData['topoPath'] ?? null,
                    'topoViewBox' => $routeData['topoViewBox'] ?? null,
                    'withSector' => $routeData['withSector'],
                    'routes' => []
                ];
            }

            $groupedRoutes[$topoName]['routes'][] = [
                'routeName' => $routeData['routeName'],
                'routeRating' => $routeData['routeRating'],
                'routeProtection' => $routeData['routeProtection'],
                'rockQuality' => $routeData['rockQuality'],
                'routeClimbingStyle' => $routeData['routeClimbingStyle'] ?? null,
                'areaId' => $routeData['areaId'],
                'rockId' => $routeData['rockId'],
                'routeId' => $routeData['routeId'],
                'routefirstAscent' => $routeData['routefirstAscent'],
                'routeyearFirstAscent' => $routeData['routeyearFirstAscent'],
                'routeComment' => $routeData['routeComment'] ?? [],
                'routeGrade' => $routeData['routeGrade'],
                'topoImage' => $routeData['topoImage'] ?? null,
                'topoPathCollection' => $routeData['topoPathCollection'] ?? null,
                'topoPath' => $routeData['topoPath'] ?? null,
                'topoViewBox' => $routeData['topoViewBox'] ?? null,
                'withSector' => $routeData['withSector'],
                'videoLink' => $routeData['videoLink']
            ];
        }
        
        // Sort by topo number
        uasort($groupedRoutes, function($a, $b) {
            return $a['topoNumber'] <=> $b['topoNumber'];
        });
        
        return $groupedRoutes;
    }
    
    /**
     * Checks if any route in a topo has sectors
     *
     * @param array $topoRoutes Routes for a specific topo
     * @return bool
     */
    public function hasSectors(array $topoRoutes): bool
    {
        foreach ($topoRoutes as $route) {
            if (isset($route['withSector']) && $route['withSector']) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Gets the first available topo image URL for a topo
     *
     * @param array $topoRoutes Routes for a specific topo
     * @return string|null
     */
    public function getFirstTopoImage(array $topoRoutes): ?string
    {
        foreach ($topoRoutes as $route) {
            if (!empty($route['topoImage'])) {
                return $route['topoImage'];
            }
        }
        return null;
    }
} 