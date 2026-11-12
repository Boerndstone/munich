<?php

namespace App\Repository;

use App\Entity\Rock;
use App\Service\GradeTranslationService;
use App\Repository\RoutesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rock[]    findAll()
 * @method Rock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RockRepository extends ServiceEntityRepository
{
    private $routesRepository;
    public function __construct(ManagerRegistry $registry, RoutesRepository $routesRepository)
    {
        parent::__construct($registry, Rock::class);
        $this->routesRepository = $routesRepository;
    }

    public function getAllRocks()
    {
        return $this->createQueryBuilder('rocks')
            ->select('count(rocks.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function amountRocks($amount_rocks)
    {
        $sql = 'SELECT * FROM area INNER JOIN rock ON area.id = rock.area_relation_id WHERE area_relation_id = :amountRocks';
        $query = $this->pdo->prepare($sql);
        $query->bindParam(':amountRocks', $amount_rocks);
        $query->execute();

        return $query->rowCount();
    }

    /**
     * @return AreaName[] Returns an array of Rocks objects
     */
    public function findRocksAreaName($areaSlug): array
    {
        return $this->createQueryBuilder('rock')
            // ->orderBy('rock.id', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->addSelect('rock')
            ->where('area.slug LIKE :areaSlug')
            ->setParameter('areaSlug', $areaSlug)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{name: string, slug: string, areaSlug: string}>
     */
    public function findSidebarRocksByAreaSlug(string $areaSlug): array
    {
        return $this->createQueryBuilder('rock')
            ->select('rock.name AS name', 'rock.slug AS slug', 'area.slug AS areaSlug')
            ->innerJoin('rock.area', 'area')
            ->where('area.slug = :areaSlug')
            ->andWhere('area.online = 1')
            ->andWhere('rock.online = 1')
            ->setParameter('areaSlug', $areaSlug)
            ->orderBy('rock.nr', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findOneByAreaSlugAndRockSlug(string $areaSlug, string $rockSlug): ?Rock
    {
        return $this->createQueryBuilder('rock')
            ->innerJoin('rock.area', 'area')
            ->andWhere('area.slug = :areaSlug')
            ->andWhere('rock.slug = :rockSlug')
            ->setParameter('areaSlug', $areaSlug)
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Rocks[] Returns an array of Rocks objects
     */
    public function findAllRocksAlphabetical()
    {
        return $this->createQueryBuilder('rock')
            ->orderBy('rock.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Rock>
     */
    public function findAllForPublicTopoSelect(): array
    {
        return $this->createQueryBuilder('rock')
            ->orderBy('rock.name', 'ASC')
            ->addOrderBy('rock.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RockName[] Returns an array of Rocks objects
     */
    public function findRockName($rockSlug): array
    {
        return $this->createQueryBuilder('rock')
            ->select('rock')
            ->where('rock.slug LIKE :rockSlug')
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getResult();
    }

    public function getRockId($rockSlug)
    {
        return $this->createQueryBuilder('rock')
            ->select('rock.id')
            ->where('rock.slug LIKE :rockSlug')
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get seasonally banned rocks as arrays (cache-friendly)
     *
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     areaName: string,
     *     areaSlug: string,
     *     banned: int|null
     * }>
     */
    public function saisonalGesperrt(): array
    {
        return $this->createQueryBuilder('rock')
            ->select(
                'rock.id',
                'rock.name',
                'rock.slug',
                'rock.banned AS banned',
                'area.name AS areaName',
                'area.slug AS areaSlug'
            )
            ->innerJoin('rock.area', 'area')
            ->where('rock.banned IN (1, 2)')
            ->andWhere('rock.slug IS NOT NULL')
            ->andWhere('area.slug IS NOT NULL')
            ->andWhere('area.online = 1')
            ->orderBy('rock.banned')
            ->getQuery()
            ->getResult();
    }


    /**
     * Rocks with coordinates for the homepage map; same route/grade aggregates as {@see getRocksInformation}.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findOnlineRocksWithCoordinatesForMap(): array
    {
        $qb = $this->createQueryBuilder('rock')
            ->select(
                'rock.id AS rockId',
                'rock.lat AS lat',
                'rock.lng AS lng',
                'rock.name AS rockName',
                'rock.slug AS rockSlug',
                'rock.childFriendly AS rockChild',
                'rock.rain AS rockRain',
                'rock.train AS rockTrain',
                'rock.bike AS rockBike',
                'rock.sunny AS rockSunny',
                'rock.previewImage AS previewImage',
                'area.name AS areaName',
                'area.slug AS areaSlug',
                'area.travelTimeMinutes AS travelTimeMinutes',
                'COUNT(DISTINCT route.id) AS amountRoutes',
                'SUM(CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN 1 ELSE 0 END) AS amountEasy',
                'SUM(CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN 1 ELSE 0 END) AS amountMiddle',
                'SUM(CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 65 THEN 1 ELSE 0 END) AS amountHard',
                'SUM(CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN 1 ELSE 0 END) AS amountProjects'
            )
            ->innerJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('rock.online = :online')
            ->andWhere('area.online = :online')
            ->andWhere('rock.lat IS NOT NULL')
            ->andWhere('rock.lng IS NOT NULL')
            ->setParameter('online', true)
            ->groupBy('rock.id')
            ->orderBy('area.sequence', 'ASC')
            ->addOrderBy('rock.nr', 'ASC');

        $groupedGrades = GradeTranslationService::gradesGroupedByUiaaChartBucket();
        foreach (range(3, 11) as $bucket) {
            $grades = $groupedGrades[$bucket] ?? [];
            $param = 'main_map_rock_grade_chart_'.$bucket;
            if ($grades === []) {
                $qb->addSelect('0 AS grade_chart_'.$bucket);
            } else {
                $qb->addSelect('SUM(CASE WHEN route.grade IN (:'.$param.') THEN 1 ELSE 0 END) AS grade_chart_'.$bucket)
                    ->setParameter($param, $grades, ArrayParameterType::STRING);
            }
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Find published crags within a radius of a given coordinate.
     * A bounding-box query keeps the database work small; the Haversine
     * calculation then applies the exact radius and orders nearest first.
     *
     * @return list<array{rockId: int, rockName: string, rockSlug: string, rockLat: string, rockLng: string, previewImage: string|null, areaName: string, areaSlug: string, distanceKm: float}>
     */
    public function findNearbyRocks(float $latitude, float $longitude, int $excludeRockId, float $radiusKm = 5.0): array
    {
        if ($radiusKm <= 0 || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return [];
        }

        $latitudeDelta = $radiusKm / 110.574;
        $longitudeDelta = $radiusKm / (111.320 * max(abs(cos(deg2rad($latitude))), 0.01));

        $candidates = $this->createQueryBuilder('rock')
            ->select(
                'rock.id AS rockId',
                'rock.name AS rockName',
                'rock.slug AS rockSlug',
                'rock.lat AS rockLat',
                'rock.lng AS rockLng',
                'rock.previewImage AS previewImage',
                'area.name AS areaName',
                'area.slug AS areaSlug'
            )
            ->innerJoin('rock.area', 'area')
            ->where('rock.online = :online')
            ->andWhere('area.online = :online')
            ->andWhere('rock.id != :excludeRockId')
            ->andWhere('rock.lat BETWEEN :minLat AND :maxLat')
            ->andWhere('rock.lng BETWEEN :minLng AND :maxLng')
            ->setParameter('online', true)
            ->setParameter('excludeRockId', $excludeRockId)
            ->setParameter('minLat', $latitude - $latitudeDelta)
            ->setParameter('maxLat', $latitude + $latitudeDelta)
            ->setParameter('minLng', $longitude - $longitudeDelta)
            ->setParameter('maxLng', $longitude + $longitudeDelta)
            ->getQuery()
            ->getArrayResult();

        $nearby = [];
        foreach ($candidates as $candidate) {
            $candidateLat = (float) $candidate['rockLat'];
            $candidateLng = (float) $candidate['rockLng'];
            $deltaLat = deg2rad($candidateLat - $latitude);
            $deltaLng = deg2rad($candidateLng - $longitude);
            $haversine = sin($deltaLat / 2) ** 2
                + cos(deg2rad($latitude)) * cos(deg2rad($candidateLat)) * sin($deltaLng / 2) ** 2;
            $distanceKm = 6371 * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine));

            if ($distanceKm <= $radiusKm) {
                $candidate['distanceKm'] = round($distanceKm, 1);
                $nearby[] = $candidate;
            }
        }

        usort($nearby, static fn (array $left, array $right): int => $left['distanceKm'] <=> $right['distanceKm']);

        return $nearby;
    }

    public function getRocksInformation($areaSlug)
    {
        $qb = $this->createQueryBuilder('rock')
            ->select(
                'rock.name as rockName',
                'rock.slug as rockSlug',
                'rock.height as rockHeight',
                'rock.childFriendly as rockChild',
                'rock.rain as rockRain',
                'rock.train as rockTrain',
                'rock.bike as rockBike',
                'rock.lat as rockLat',
                'rock.lng as rockLng',
                'rock.orientation as rockOrientation',
                'rock.sunny as rockSunny',
                'rock.image as rockImage',
                'rock.previewImage as previewImage',
                'rock.banned as banned',
                'area.name as areaName',
                'area.slug as areaSlug',
                'COUNT(DISTINCT route.id) AS amountRoutes',
                'SUM(CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN 1 ELSE 0 END) AS amountEasy',
                'SUM(CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN 1 ELSE 0 END) AS amountMiddle',
                'SUM(CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 65 THEN 1 ELSE 0 END) AS amountHard',
                'SUM(CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN 1 ELSE 0 END) AS amountProjects'
            )
            ->orderBy('rock.nr', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('area.slug LIKE :areaSlug')
            ->andWhere('rock.online = 1')
            ->setParameter('areaSlug', $areaSlug)
            ->groupBy('rock.id');

        $groupedGrades = GradeTranslationService::gradesGroupedByUiaaChartBucket();
        foreach (range(3, 11) as $bucket) {
            $grades = $groupedGrades[$bucket] ?? [];
            $param = 'rock_grade_chart_'.$bucket;
            if ($grades === []) {
                $qb->addSelect('0 AS grade_chart_'.$bucket);
            } else {
                $qb->addSelect('SUM(CASE WHEN route.grade IN (:'.$param.') THEN 1 ELSE 0 END) AS grade_chart_'.$bucket)
                    ->setParameter($param, $grades, ArrayParameterType::STRING);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function getRockInformation($rockSlug)
    {
        $qb = $this->createQueryBuilder('rock')
            ->select(
                'rock.name as rockName',
                'rock.slug as rockSlug',
                'rock.height as rockHeight',
                'rock.childFriendly as rockChild',
                'rock.rain as rockRain',
                'rock.train as rockTrain',
                'rock.bike as rockBike',
                'rock.lat as rockLat',
                'rock.lng as rockLng',
                'rock.zone as rockZone',
                'rock.zoom as rockZoom',
                'rock.pathCoordinates as pathCoordinates',
                'rock.orientation as rockOrientation',
                'rock.sunny as rockSunny',
                'rock.image as rockImage',
                'rock.season as rockSeason',
                'rock.banned as rockBanned',
                'area.name as areaName',
                'area.slug as areaSlug',
                'COUNT(DISTINCT route.id) AS amountRoutes',
                'SUM(CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN 1 ELSE 0 END) AS amountEasy',
                'SUM(CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN 1 ELSE 0 END) AS amountMiddle',
                'SUM(CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 65 THEN 1 ELSE 0 END) AS amountHard',
                'SUM(CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN 1 ELSE 0 END) AS amountProjects'
            )
            ->orderBy('rock.id', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('rock.slug LIKE :rockSlug')
            ->andWhere('rock.online = 1')
            ->setParameter('rockSlug', $rockSlug)
            ->groupBy('rock.id');

        $groupedGrades = GradeTranslationService::gradesGroupedByUiaaChartBucket();
        foreach (range(3, 11) as $bucket) {
            $grades = $groupedGrades[$bucket] ?? [];
            $param = 'single_rock_grade_chart_'.$bucket;
            if ($grades === []) {
                $qb->addSelect('0 AS grade_chart_'.$bucket);
            } else {
                $qb->addSelect('SUM(CASE WHEN route.grade IN (:'.$param.') THEN 1 ELSE 0 END) AS grade_chart_'.$bucket)
                    ->setParameter($param, $grades, ArrayParameterType::STRING);
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function getRoutesTopo($rockSlug)
    {
        return $this->createQueryBuilder('rock')
            ->select(
                'area.id as areaId',
                'rock.id as rockId',
                'routes.id as routeId',
                'routes.name as routeName',
                'routes.grade as routeGrade',
                'routes.topoId as routeTopoId',
                'routes.rating as routeRating',
                'routes.protection as routeProtection',
                'routes.rockQuality as rockQuality',
                'routes.climbingStyle as routeClimbingStyle',
                'routes.firstAscent as routefirstAscent',
                'routes.yearFirstAscent as routeyearFirstAscent',
                // 'comments.comment as routeComment',
                'topo.name as topoName',
                'topo.number as topoNumber',
                'videos.videoLink as videoLink',
                'topo.image as topoImage',
                'topo.pathCollection as topoPathCollection',
                'topo.withSector as withSector'

            )
            ->innerJoin('rock.area', 'area')
            ->innerJoin('rock.routes', 'routes')
            // ->leftJoin('routes.comments', 'comments')
            ->innerJoin('App\Entity\Topo', 'topo', 'WITH', 'topo.rocks = rock')
            ->leftJoin('App\Entity\Videos', 'videos', 'WITH', 'videos.videoRoutes = routes.id')
            ->where('rock.slug LIKE :rockSlug')
            ->andWhere('routes.rock = topo.rocks')
            ->andWhere('routes.topoId = topo.number')
            ->setParameter('rockSlug', $rockSlug)
            ->orderBy('rock.id', 'ASC')
            ->addOrderBy('topo.number', 'ASC')
            ->addOrderBy('routes.nr', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getRouteGradesForRocks($areaSlug)
    {
        return $this->createQueryBuilder('rock')
            ->select(
                'rock.id as rockId',
                'rock.slug as rockSlug',
                'route.gradeNo as gradeNo'
            )
            ->leftJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('area.slug LIKE :areaSlug')
            ->andWhere('rock.online = 1')
            ->andWhere('route.gradeNo IS NOT NULL')
            ->setParameter('areaSlug', $areaSlug)
            ->orderBy('rock.id', 'ASC')
            ->addOrderBy('route.gradeNo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getCommentsForRoutes($rockSlug)
    {
        return $this->routesRepository->createQueryBuilder('routes')
            ->select('routes.id as routeId', 'comments.comment as routeComment', 'comments.datetime as date', 'user.username as username')
            ->innerJoin('routes.comments', 'comments')
            ->leftJoin('comments.user', 'user')
            ->innerJoin('routes.rock', 'rock')
            ->where('rock.slug LIKE :rockSlug')
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getResult();
    }

    public function search($query)
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.area', 'area')
            ->addSelect('area')
            ->where('r.name LIKE :query')
            ->andWhere('r.online = 1')
            ->setParameter('query', "%$query%")
            ->orderBy('r.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find rocks by attributes: child friendly, sunny, rain protected, train, bike.
     *
     * @param array{childFriendly?: bool, sunny?: bool, rainProtected?: bool, train?: bool, bike?: bool} $filters
     * @return Rock[]
     */
    public function findByAttributes(array $filters, ?string $areaSlug = null): array
    {
        $qb = $this->createQueryBuilder('rock')
            ->leftJoin('rock.area', 'area')
            ->addSelect('area')
            ->where('rock.online = 1')
            ->orderBy('rock.name', 'ASC');

        if (!empty($areaSlug)) {
            $qb->andWhere('area.slug = :areaSlug')
                ->setParameter('areaSlug', $areaSlug);
        }

        if (!empty($filters['childFriendly'])) {
            $qb->andWhere('rock.childFriendly = 1');
        }
        if (!empty($filters['sunny'])) {
            $qb->andWhere('rock.sunny = 1');
        }
        if (!empty($filters['rainProtected'])) {
            $qb->andWhere('rock.rain = 1');
        }
        if (!empty($filters['train'])) {
            $qb->andWhere('rock.train = 1');
        }
        if (!empty($filters['bike'])) {
            $qb->andWhere('rock.bike = 1');
        }

        return $qb
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function findWithTranslations($slug, $locale)
    {
        return $this->createQueryBuilder('r')
            ->select('t.description, t.access, t.nature, t.flowers')
            ->leftJoin('r.translations', 't')
            ->andWhere('r.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();
    }


    public function hasTranslationDescription($slug, $locale)
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('COUNT(t.id)')
            ->leftJoin('r.translations', 't')
            ->andWhere('r.slug = :slug')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.description IS NOT NULL')
            ->setParameter('slug', $slug)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
