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

    /**
     * Get latest routes as arrays (cache-friendly)
     *
     * @return array<int, array{id: int, name: string, grade: string, rockName: string, rockSlug: string, areaSlug: string}>
     */
    public function latestRoutes(): array
    {
        return $this->createQueryBuilder('routes')
            ->select(
                'routes.id',
                'routes.name',
                'routes.grade',
                'rock.name AS rockName',
                'rock.slug AS rockSlug',
                'area.slug AS areaSlug'
            )
            ->innerJoin('routes.rock', 'rock')
            ->innerJoin('routes.area', 'area')
            ->where('rock.slug IS NOT NULL')
            ->andWhere('area.slug IS NOT NULL')
            ->andWhere('rock.online = 1')
            ->andWhere('area.online = 1')
            ->orderBy('routes.yearFirstAscent', 'DESC')
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

    /**
     * Recompute grade_no for every route from grade (same rules as {@link Routes::setGradeNoFromGrade()},
     * including slash fallback via {@link \App\Service\GradeTranslationService::gradeToMappedNumber()}).
     */
    public function updateGrades(): int
    {
        $em = $this->getEntityManager();
        $processed = 0;
        foreach ($this->createQueryBuilder('r')->select('r')->getQuery()->toIterable() as $route) {
        $batchSize = 100;
        $processed = 0;

        $em->wrapInTransaction(function () use ($em, $batchSize, &$processed): void {
            foreach ($this->createQueryBuilder('r')->select('r')->getQuery()->toIterable() as $route) {
                /** @var Routes $route */
                $route->setGradeNoFromGrade($route->getGrade());
                ++$processed;

                if ($processed % $batchSize === 0) {
                    $em->flush();
                    $em->clear();
                }
            }

            $em->flush();
            $em->clear();
        });
        return $processed;
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
            ->addSelect('rock', 'area')
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
            ->addSelect('rock', 'area')
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
            ->addSelect('rock', 'area')
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

    public function countByGrades(array $gradeRanges, ?string $areaSlug = null): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->where('(area.online = 1 OR rock.online = 1)');

        $this->applyGradeConditions($qb, $gradeRanges, $areaSlug);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByGrades(array $gradeRanges, ?string $areaSlug = null, ?int $limit = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->addSelect('rock', 'area')
            ->where('(area.online = 1 OR rock.online = 1)');

        $this->applyGradeConditions($qb, $gradeRanges, $areaSlug);

        $qb = $qb->orderBy('r.name', 'ASC');
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }
        return $qb->getQuery()->getResult();
    }

    private function applyGradeConditions($qb, array $gradeRanges, ?string $areaSlug): void
    {
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

        if (!empty($areaSlug)) {
            $qb->andWhere('area.slug = :areaSlug')
               ->setParameter('areaSlug', $areaSlug);
        }
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

    /**
     * Get the top 100 most difficult routes in an area (cache-friendly version)
     * Returns arrays instead of entities for proper serialization
     */
    public function findTop100ByAreaCached(int $areaId): array
    {
        return $this->createQueryBuilder('r')
            ->select(
                'r.id',
                'r.name',
                'r.grade',
                'r.gradeNo',
                'r.firstAscent',
                'r.yearFirstAscent',
                'r.rating',
                'r.protection',
                'r.rockQuality',
                'r.climbingStyle',
                'rock.name AS rockName',
                'rock.slug AS rockSlug',
                'area.slug AS areaSlug'
            )
            ->leftJoin('r.rock', 'rock')
            ->leftJoin('r.area', 'area')
            ->where('area.id = :areaId')
            ->andWhere('r.gradeNo IS NOT NULL')
            ->andWhere('rock.online = 1')
            ->setParameter('areaId', $areaId)
            ->orderBy('r.gradeNo', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }
}
