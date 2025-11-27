<?php

namespace App\Repository;

use App\Entity\Area;
use App\Entity\Rock;
use App\Entity\Routes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Routes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Routes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Routes[]    findAll()
 * @method Routes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoutesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Routes::class);
    }

    public function getAllRoutes()
    {
        return $this->createQueryBuilder('routes')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllRoutesBelowSix()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.id', 'ASC')
            ->where('routes.gradeNo < 15')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllRoutesBelowEight()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.id', 'ASC')
            ->where('routes.gradeNo >= 15 and routes.gradeNo <= 29')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllRoutesGreaterEight()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.id', 'ASC')
            ->where('routes.gradeNo > 29')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllProjectds()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.id', 'ASC')
            ->where('routes.gradeNo is NULL')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllAlreadyClimbed()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.id', 'ASC')
            ->where('routes.climbed = 1')
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function latestRoutes()
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.yearFirstAscent', 'DESC')
            ->innerJoin('routes.rock', 'routes_rock')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    public function latestRoutesPage($calculateDate)
    {
        return $this->createQueryBuilder('routes')
            ->orderBy('routes.yearFirstAscent', 'DESC')
            ->innerJoin('routes.rock', 'routes_rock')
            ->where('routes.yearFirstAscent >= :calculateDate')
            ->setParameter('calculateDate', $calculateDate)
            ->getQuery()
            ->getResult();
    }

    public function getGrades($area, $gradeLow, $gradeHigh)
    {
        return $this->createQueryBuilder('routes')
            ->innerJoin('routes.area', 'area')
            ->andWhere('routes.area = :area')
            ->setParameter('area', $area)
            ->andWhere('routes.gradeNo > :gradeLow')
            ->setParameter('gradeLow', $gradeLow)
            ->andWhere('routes.gradeNo <= :gradeHigh')
            ->setParameter('gradeHigh', $gradeHigh)
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getProjects($area, $gradeLow, $gradeHigh)
    {
        return $this->createQueryBuilder('routes')
            ->innerJoin('routes.area', 'area')
            ->andWhere('routes.area = :area')
            ->setParameter('area', $area)
            ->andWhere('routes.gradeNo > :gradeLow')
            ->setParameter('gradeLow', $gradeLow)
            ->andWhere('routes.gradeNo <= :gradeHigh')
            ->setParameter('gradeHigh', $gradeHigh)
            ->select('count(routes.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }


    public function findAllClimbedRoutes(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.climbed = true')
            ->getQuery()
            ->getResult();
    }

    public function findClimbedRoutesByArea(Area $area): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.area = :area')
            ->andWhere('r.climbed = true')
            ->setParameter('area', $area)
            ->getQuery()
            ->getResult();
    }

    public function updateGrades(): int
    {
        $qb = $this->createQueryBuilder('r');

        $qb->update()
            ->set('r.gradeNo', 'CASE 
                WHEN r.grade = \'4\' THEN 9  
                WHEN r.grade = \'4a\' THEN 10 
                WHEN r.grade = \'4b\' THEN 11 
                WHEN r.grade = \'4c\' THEN 12 
                WHEN r.grade = \'4c+\' THEN 13
                WHEN r.grade = \'5a\' THEN 14 
                WHEN r.grade = \'5a+\' THEN 15 
                WHEN r.grade = \'5b\' THEN 16 
                WHEN r.grade = \'5b+\' THEN 17 
                WHEN r.grade = \'5c\' THEN 18 
                WHEN r.grade = \'5c+\' THEN 19 
                WHEN r.grade = \'6a\' THEN 20 
                WHEN r.grade = \'6a/6a+\' THEN 21 
                WHEN r.grade = \'6a+\' THEN 22 
                WHEN r.grade = \'6a+/6b\' THEN 23 
                WHEN r.grade = \'6b\' THEN 24 
                WHEN r.grade = \'6b/6b+\' THEN 25 
                WHEN r.grade = \'6b+\' THEN 27 
                WHEN r.grade = \'6c\' THEN 28 
                WHEN r.grade = \'6c+\' THEN 30 
                WHEN r.grade = \'6c+/7a\' THEN 31 
                WHEN r.grade = \'7a\' THEN 32 
                WHEN r.grade = \'7a/7a+\' THEN 33 
                WHEN r.grade = \'7a+\' THEN 35 
                WHEN r.grade = \'7b\' THEN 36 
                WHEN r.grade = \'7b+\' THEN 37 
                WHEN r.grade = \'7b+/7c\' THEN 39 
                WHEN r.grade = \'7c\' THEN 40 
                WHEN r.grade = \'7c/7c+\' THEN 41 
                WHEN r.grade = \'7c+\' THEN 43 
                WHEN r.grade = \'8a\' THEN 44 
                WHEN r.grade = \'8a/8a+\' THEN 45 
                WHEN r.grade = \'8a+\' THEN 46 
                WHEN r.grade = \'8a+/8b\' THEN 47 
                WHEN r.grade = \'8b\' THEN 48 
                WHEN r.grade = \'8b/8b+\' THEN 50 
                WHEN r.grade = \'8b+\' THEN 51 
                WHEN r.grade = \'8b+/8c\' THEN 52 
                WHEN r.grade = \'8c\' THEN 54 
                WHEN r.grade = \'8c+\' THEN 55 
                WHEN r.grade = \'8c+/9a\' THEN 56 
                WHEN r.grade = \'9a\' THEN 57 
                WHEN r.grade = \'9a/9a+\' THEN 58 
                WHEN r.grade = \'9a+\' THEN 59 
                WHEN r.grade = \'0\' THEN 500 
                WHEN r.grade = \'1\' THEN 1 
                WHEN r.grade = \'2-\' THEN 2 
                WHEN r.grade = \'2\' THEN 3 
                WHEN r.grade = \'2+\' THEN 4 
                WHEN r.grade = \'3-\' THEN 5  
                WHEN r.grade = \'3\' THEN 6  
                WHEN r.grade = \'3+\' THEN 7  
                WHEN r.grade = \'4-\' THEN 8  
                WHEN r.grade = \'4\' THEN 9 
                WHEN r.grade = \'4+\' THEN 10   
                WHEN r.grade = \'5-\' THEN 11  
                WHEN r.grade = \'5\' THEN 12 
                WHEN r.grade = \'5/5+\' THEN 13   
                WHEN r.grade = \'5+\' THEN 14  
                WHEN r.grade = \'5+/6-\' THEN 15  
                WHEN r.grade = \'6-\' THEN 16  
                WHEN r.grade = \'6-/6\' THEN 17  
                WHEN r.grade = \'6\' THEN 18 
                WHEN r.grade = \'6/6+\' THEN 19   
                WHEN r.grade = \'6+\' THEN 20 
                WHEN r.grade = \'6+/7-\' THEN 21   
                WHEN r.grade = \'7-\' THEN 22  
                WHEN r.grade = \'7-/7\' THEN 23 
                WHEN r.grade = \'7\' THEN 24 
                WHEN r.grade = \'7/7+\' THEN 25 
                WHEN r.grade = \'7+\' THEN 27 
                WHEN r.grade = \'7+/8-\' THEN 28 
                WHEN r.grade = \'8-\' THEN 30 
                WHEN r.grade = \'8-/8\' THEN 31 
                WHEN r.grade = \'8\' THEN 32 
                WHEN r.grade = \'8/8+\' THEN 33 
                WHEN r.grade = \'8+\' THEN 35 
                WHEN r.grade = \'8+/9-\' THEN 36 
                WHEN r.grade = \'9-\' THEN 37 
                WHEN r.grade = \'9-/9\' THEN 39 
                WHEN r.grade = \'9\' THEN 40 
                WHEN r.grade = \'9/9+\' THEN 41 
                WHEN r.grade = \'9+\' THEN 43 
                WHEN r.grade = \'9+/10-\' THEN 44 
                WHEN r.grade = \'10-\' THEN 46 
                WHEN r.grade = \'10-/10\' THEN 47 
                WHEN r.grade = \'10\' THEN 48 
                WHEN r.grade = \'10/10+\' THEN 50 
                WHEN r.grade = \'10+\' THEN 51 
                WHEN r.grade = \'10+/11-\' THEN 52 
                WHEN r.grade = \'11-\' THEN 54 
                WHEN r.grade = \'11-/11\' THEN 55
                WHEN r.grade = \'11\' THEN 57 
                ELSE r.gradeNo
            END')
            ->getQuery()
            ->execute();

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Routes[] Returns an array of Routes objects
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->leftJoin('r.relatesToRoute', 'firstAscencionist')
            ->addSelect('rock', 'area', 'firstAscencionist')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFirstAscent(string $name): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->leftJoin('r.relatesToRoute', 'firstAscencionist')
            ->addSelect('rock', 'area', 'firstAscencionist')
            ->where('r.firstAscent LIKE :name')
            ->andWhere('(area.online = 1 OR rock.online = 1)')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByFirstAscentAndGrades(string $name, array $gradeRanges): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->leftJoin('r.relatesToRoute', 'firstAscencionist')
            ->addSelect('rock', 'area', 'firstAscencionist')
            ->where('r.firstAscent LIKE :name')
            ->setParameter('name', '%' . $name . '%');

        // Build grade conditions based on selected ranges
        $gradeConditions = [];
        foreach ($gradeRanges as $range) {
            $gradeConditions[] = 'r.grade IN (:grades_' . $range . ')';
            $qb->setParameter('grades_' . $range, $this->getGradesForRange($range));
        }

        if (!empty($gradeConditions)) {
            $qb->andWhere('(' . implode(' OR ', $gradeConditions) . ')');
        }

        return $qb->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByGrades(array $gradeRanges, ?string $areaSlug = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->addSelect('rock', 'area')
            ->where('(area.online = 1 OR rock.online = 1)');

        // Build grade conditions based on selected ranges using numerical values
        $gradeConditions = [];
        foreach ($gradeRanges as $range) {
            $rangeValues = $this->getNumericalRangeForGrade($range);
            if (!empty($rangeValues)) {
                $gradeConditions[] = 'r.gradeNo BETWEEN :min_' . $range . ' AND :max_' . $range;
                $qb->setParameter('min_' . $range, $rangeValues['min']);
                $qb->setParameter('max_' . $range, $rangeValues['max']);
            }
        }

        if (!empty($gradeConditions)) {
            $qb->andWhere('(' . implode(' OR ', $gradeConditions) . ')');
        }

        // Add area filter if specified
        if (!empty($areaSlug)) {
            $qb->andWhere('area.slug = :areaSlug')
               ->setParameter('areaSlug', $areaSlug);
        }

        return $qb->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function getNumericalRangeForGrade(string $range): array
    {
        $gradeRanges = [
            '1' => ['min' => 1, 'max' => 1],      // Grade 1
            '2' => ['min' => 2, 'max' => 4],      // Grade 2- to 2+
            '3' => ['min' => 5, 'max' => 7],      // Grade 3- to 3+
            '4' => ['min' => 8, 'max' => 10],     // Grade 4- to 4+
            '5' => ['min' => 11, 'max' => 15],    // Grade 5- to 5+/6-
            '6' => ['min' => 16, 'max' => 20],    // Grade 6- to 6+
            '7' => ['min' => 21, 'max' => 27],    // Grade 6+/7- to 7+
            '8' => ['min' => 28, 'max' => 35],   // Grade 7+/8- to 8+
            '9' => ['min' => 36, 'max' => 43],   // Grade 8+/9- to 9+
            '10' => ['min' => 44, 'max' => 51],  // Grade 9+/10- to 10+
            '11' => ['min' => 52, 'max' => 57], // Grade 10+/11- to 11
        ];

        return $gradeRanges[$range] ?? [];
    }

    public function search(string $query, ?string $grade = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->addSelect('rock', 'area')
            ->where('r.name LIKE :query')
            ->andWhere('(area.online = 1 OR rock.online = 1)')
            ->setParameter('query', '%' . $query . '%');

        // Add grade filter if specified
        if (!empty($grade)) {
            $rangeValues = $this->getNumericalRangeForGrade($grade);
            if (!empty($rangeValues)) {
                $qb->andWhere('r.gradeNo BETWEEN :min_grade AND :max_grade')
                   ->setParameter('min_grade', $rangeValues['min'])
                   ->setParameter('max_grade', $rangeValues['max']);
            }
        }

        return $qb->orderBy('r.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function searchByGrade(string $grade, ?string $areaSlug = null): array
    {
        $rangeValues = $this->getNumericalRangeForGrade($grade);
        if (empty($rangeValues)) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->addSelect('rock', 'area')
            ->where('r.gradeNo BETWEEN :min_grade AND :max_grade')
            ->andWhere('(area.online = 1 OR rock.online = 1)')
            ->setParameter('min_grade', $rangeValues['min'])
            ->setParameter('max_grade', $rangeValues['max']);

        // Add area filter if specified
        if (!empty($areaSlug)) {
            $qb->andWhere('area.slug = :areaSlug')
               ->setParameter('areaSlug', $areaSlug);
        }

        return $qb->orderBy('r.name', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the top 100 most difficult routes in an area
     * Ordered by gradeNo descending (highest difficulty first)
     * 
     * @param Area $area
     * @return Routes[]
     */
    public function findTop100ByArea(Area $area): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->addSelect('rock', 'area')
            ->where('r.area = :area')
            ->andWhere('r.gradeNo IS NOT NULL')
            ->andWhere('rock.online = 1')
            ->setParameter('area', $area)
            ->orderBy('r.gradeNo', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
