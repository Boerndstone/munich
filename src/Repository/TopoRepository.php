<?php

namespace App\Repository;

use App\Entity\Rock;
use App\Entity\Topo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Topo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Topo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Topo[]    findAll()
 * @method Topo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TopoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Topo::class);
    }

    /**
     * @return Topos[] Returns an array of Rocks objects
     */
    public function getTopos($rockId): array
    {
        return $this->createQueryBuilder('topo')
            ->select(
                'topo.name as topoName',
                'topo.number as topoNumber'
            )
            ->innerJoin('topo.rocks', 'rocks')
            ->where('rocks.id LIKE :rockId')
            ->andWhere('topo.withSector = 1')
            ->setParameter('rockId', $rockId)
            ->orderBy('topo.number', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * All topos belonging to a rock (Route.topo_id matches Topo.number for this rock).
     *
     * @return list<Topo>
     */
    public function findAllForRock(Rock $rock): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.rocks = :rock')
            ->setParameter('rock', $rock)
            ->orderBy('t.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRoutesByTopoNumber($topoNumber)
    {
        return $this->createQueryBuilder('topo')
            ->select('route')
            ->leftJoin('topo.rocks', 'rock')
            ->leftJoin('rock.routes', 'routes')
            ->where('topo.number = :topoNumber')
            ->setParameter('topoNumber', $topoNumber)
            ->getQuery()
            ->getResult();
    }
}
