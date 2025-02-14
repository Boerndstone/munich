<?php

namespace App\Repository;

use App\Entity\Area;
use App\Entity\Routes;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\Criteria;

/**
 * @method Area|null find($id, $lockMode = null, $lockVersion = null)
 * @method Area|null findOneBy(array $criteria, array $orderBy = null)
 * @method Area[]    findAll()
 * @method Area[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Area::class);
    }

    // Doctrine's normal mode is to always return objects, not an array of data.
    public function findAllOrderedBy()
    {
        $qb = $this->createQueryBuilder('area')
            ->addOrderBy('area.name', 'DESC');
        $query = $qb->getQuery();

        return $query->execute();
    }

    public function search($term)
    {
        return $this->createQueryBuilder('area')
            // always use andWhere!!!!
            // ->andWhere('area.name LIKE :searchTerm OR area.orientation LIKE :searchTerm OR route.name LIKE :searchTerm')
            //->leftJoin('area.routes', 'route')
            //->addSelect('route')
            ->select('area.name as areaName')
            ->andWhere('area.name LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $term . '%')
            ->getQuery()
            ->execute();
    }

    public function getAllAreas()
    {
        return $this->createQueryBuilder('areas')
            ->select('count(areas.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // In use for Dashboard Backend
    /**
     * @return Area[] Returns an array of Area objects
     */
    public function findAllAreasAlphabetical()
    {
        return $this->createQueryBuilder('area')
            ->orderBy('area.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Area[] Returns an array of Area objects
     */
    public function getAreasFrontend()
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.sequence', 'ASC')
            ->where('a.online = 1')
            ->getQuery()
            ->getResult();
    }

    public function getAreasInformation()
    {
        $qb = $this->createQueryBuilder('area')
            ->select(
                'area.id as areaId',
                'area.name as name',
                'area.slug as slug',
                'area.image as image',
                'area.lat as lat',
                'area.lng as lng',
                'COUNT(DISTINCT route.id) AS routes',
                'COUNT(DISTINCT rock.id) AS rocks',
                'COUNT(DISTINCT CASE WHEN route.gradeNo > 0 AND route.gradeNo <= 15 THEN route.id ELSE 0 END) AS amountEasy',
                'COUNT(DISTINCT CASE WHEN route.gradeNo > 15 AND route.gradeNo <= 29 THEN route.id ELSE 0 END) AS amountMiddle',
                'COUNT(DISTINCT CASE WHEN route.gradeNo > 29 AND route.gradeNo <= 60 THEN route.id ELSE 0 END) AS amountHard',
                'COUNT(DISTINCT CASE WHEN route.gradeNo = 0 OR route.gradeNo IS NULL THEN route.id ELSE 0 END) AS amountProjects'
            )
            ->leftJoin('area.routes', 'route')
            ->leftJoin('area.rocks', 'rock')
            ->where('area.online = 1')
            ->groupBy('area.id, area.name')
            ->orderBy('area.sequence');

        return $qb->getQuery()->getResult();
    }

    public function sidebarNavigation()
    {
        $qb = $this->createQueryBuilder('area')
            ->select(
                'PARTIAL area.{id, name, slug, image}',
                'PARTIAL rock.{id, name, slug}'
            )
            ->leftJoin('area.rocks', 'rock')
            ->where('area.online = 1')
            ->andWhere('rock.online = 1')
            ->orderBy('area.sequence')
            ->addOrderBy('rock.nr', 'ASC');

        return $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function sidebarNavigationNew()
    {
        $qb = $this->createQueryBuilder('area')
            ->select('area.id', 'area.name', 'area.image', 'rock.id AS rockId', 'rock.name AS rockName', 'rock.slug AS rockSlug')
            ->leftJoin('area.rocks', 'rock')
            ->where('area.online = 1')
            ->orderBy('area.sequence');

        $result = $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $groupedResult = [];

        foreach ($result as $row) {
            $areaId = $row['id'];
            $areaName = $row['name'];
            $image = $row['image'];
            $rockId = $row['rockId'];
            $rockName = $row['rockName'];
            $rockSlug = $row['rockSlug'];

            if (!isset($groupedResult[$areaId])) {
                $groupedResult[$areaId] = [
                    'id' => $areaId,
                    'name' => $areaName,
                    'image' => $image,
                    'rocks' => [],
                ];
            }

            if ($rockId !== null) {
                $groupedResult[$areaId]['rocks'][] = [
                    'rockId' => $rockId,
                    'rockName' => $rockName,
                    'rockSlug' => $rockSlug,
                ];
            }
        }

        return array_values($groupedResult); // Re-index the array
    }

    // public function sidebarNavigation()
    // {
    //     $qb = $this->createQueryBuilder('area')
    //         ->select('area.id', 'area.name as areaName', 'area.image', 'rock.id', 'rock.name')
    //         ->leftJoin('area.rocks', 'rock')
    //         ->addSelect('rock.id', 'rock.name as rockName')
    //         ->where('area.online = 1')
    //         ->orderBy('area.sequence');

    //     //return  $qb->getQuery()->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    //     return $qb->getQuery()->getResult();
    // }

    public function getAreasFooter()
    {
        $qb = $this->createQueryBuilder('area')
            ->select(
                'area.id as areaId',
                'area.name as name',
                'area.slug as slug',
            )
            ->leftJoin('area.rocks', 'rock')
            ->where('area.online = 1')
            ->groupBy('area.id, area.name')
            ->orderBy('area.sequence');

        return $qb->getQuery()->getResult();
    }
}
