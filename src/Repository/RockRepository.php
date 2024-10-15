<?php

namespace App\Repository;

use App\Entity\Rock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\RoutesRepository;

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
        $rocks = $query->rowCount();

        return $rocks;
    }

    /**
     * @return Rocks[] Returns an array of Rocks objects
     */
    public function findSearchTerm(string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            // ->addCriteria(self::createApprovedCriteria())
            ->orderBy('rock.id', 'ASC')
            ->innerJoin('rock.area', 'area')
            ->addSelect('rock');
        if ($search) {
            $queryBuilder->andWhere('rock.name LIKE :searchTerm OR area.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $search . '%');
        }

        return $queryBuilder
            // ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AreaName[] Returns an array of Rocks objects
     */
    public function findRocksAreaName($areaSlug): array
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            // ->orderBy('rock.id', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->addSelect('rock')
            ->where('area.slug LIKE :areaSlug')
            ->setParameter('areaSlug', $areaSlug)
            ->getQuery()
            ->getResult();

        return $queryBuilder;
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
     * @return RockName[] Returns an array of Rocks objects
     */
    public function findRockName($rockSlug): array
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            ->select('rock')
            ->where('rock.slug LIKE :rockSlug')
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getResult();

        return $queryBuilder;
    }

    public function getRockId($rockSlug)
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            ->select('rock.id')
            ->where('rock.slug LIKE :rockSlug')
            ->setParameter('rockSlug', $rockSlug)
            ->getQuery()
            ->getSingleScalarResult();

        return $queryBuilder;
    }

    public function saisonalGesperrt()
    {
        return $this->createQueryBuilder('rock')
            ->orderBy('rock.name', 'ASC')
            ->where('rock.banned = 1')
            ->orWhere('rock.banned = 2')
            ->orderBy('rock.banned')
            ->getQuery()
            ->getResult();
    }


    public function getRocksInformation($areaSlug)
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            ->select(
                'rock.name as rockName',
                'rock.slug as rockSlug',
                'rock.height as rockHeight',
                'rock.childFriendly as rockChild',
                'rock.rain as rockRain',
                'rock.train as rockTrain',
                'rock.lat as rockLat',
                'rock.lng as rockLng',
                'rock.orientation as rockOrientation',
                'rock.sunny as rockSunny',
                'rock.image as rockImage',
                'rock.previewImage as previewImage',
                'area.name as areaName',
                'area.slug as areaSlug',
                'COUNT(DISTINCT route.id) AS amountRoutes',
                'SUM(CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN 1 ELSE 0 END) AS amountEasy',
                'SUM(CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN 1 ELSE 0 END) AS amountMiddle',
                'SUM(CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 60 THEN 1 ELSE 0 END) AS amountHard',
                'SUM(CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN 1 ELSE 0 END) AS amountProjects'
            )
            ->orderBy('rock.nr', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('area.slug LIKE :areaSlug')
            ->andWhere('rock.online = 1')
            ->setParameter('areaSlug', $areaSlug)
            ->groupBy('rock.id')
            ->getQuery()
            ->getResult();

        return $queryBuilder;
    }

    public function getRockInformation($rockSlug)
    {
        $queryBuilder = $this->createQueryBuilder('rock')
            ->select(
                'rock.name as rockName',
                'rock.slug as rockSlug',
                'rock.height as rockHeight',
                'rock.childFriendly as rockChild',
                'rock.rain as rockRain',
                'rock.lat as rockLat',
                'rock.lng as rockLng',
                'rock.zone as rockZone',
                'rock.zoom as rockZoom',
                'rock.pathCoordinates as pathCoordinates',
                'rock.orientation as rockOrientation',
                'rock.sunny as rockSunny',
                'rock.image as rockImage',
                'rock.season as rockSeason',
                'rock.description as rockDescription',
                'rock.access as rockAccess',
                'rock.nature as rockNature',
                'rock.headerImage as rockheaderImage',
                'rock.banned as rockBanned',
                'area.name as areaName',
                'area.slug as areaSlug',
                'COUNT(DISTINCT route.id) AS amountRoutes',
                'SUM(CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN 1 ELSE 0 END) AS amountEasy',
                'SUM(CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN 1 ELSE 0 END) AS amountMiddle',
                'SUM(CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 60 THEN 1 ELSE 0 END) AS amountHard',
                'SUM(CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN 1 ELSE 0 END) AS amountProjects'
            )
            ->orderBy('rock.id', 'ASC')
            ->leftJoin('rock.area', 'area')
            ->leftJoin('rock.routes', 'route')
            ->where('rock.slug LIKE :rockSlug')
            ->andWhere('rock.online = 1')
            ->setParameter('rockSlug', $rockSlug)
            ->groupBy('rock.id')
            ->getQuery()
            ->getResult();

        return $queryBuilder;
    }

    public function getRoutesTopo($rockSlug)
    {
        $queryBuilder = $this->createQueryBuilder('rock')
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
                'routes.firstAscent as routefirstAscent',
                'routes.yearFirstAscent as routeyearFirstAscent',
                'routes.description as routeDescription',
                // 'comments.comment as routeComment',
                'topo.name as topoName',
                'topo.number as topoNumber',
                'videos.videoLink as videoLink',
                'topo.svg as topoSvg',
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

        return $queryBuilder;
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
            ->where('r.name LIKE :query')
            ->setParameter('query', "%$query%")
            ->getQuery()
            ->getResult();
    }
}
