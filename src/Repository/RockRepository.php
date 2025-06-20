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
        return $this->createQueryBuilder('rock')
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
                'rock.banned as banned',
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
    }

    public function getRockInformation($rockSlug)
    {
        return $this->createQueryBuilder('rock')
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
            ->andWhere('r.online = 1')
            ->setParameter('query', "%$query%")
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
