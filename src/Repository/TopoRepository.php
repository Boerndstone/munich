<?php

namespace App\Repository;

use App\Entity\Rock;
use App\Entity\Topo;
use App\Entity\User;
use App\Service\RockAccessService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * Topos for a rock usable as route topo targets (Route.topo_id stores Topo.number).
     * Rows with no number are omitted; they cannot map to routes.topo_id.
     *
     * @return list<Topo>
     */
    public function findAllForRock(Rock $rock): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.rocks = :rock')
            ->andWhere('t.number IS NOT NULL')
            ->setParameter('rock', $rock)
            ->orderBy('t.number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Topo>
     */
    public function findAllEditableForUser(UserInterface $user, RockAccessService $rockAccess): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $qb = $this->createQueryBuilder('entity')
            ->orderBy('entity.name', 'ASC');
        $rockAccess->restrictTopoQueryBuilder($qb, $user);

        /** @var list<Topo> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
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
