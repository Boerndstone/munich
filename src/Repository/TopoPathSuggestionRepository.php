<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TopoPathSuggestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TopoPathSuggestion>
 */
class TopoPathSuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopoPathSuggestion::class);
    }

    /**
     * @return list<TopoPathSuggestion>
     */
    public function findPendingOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :pending')
            ->setParameter('pending', 'pending')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
