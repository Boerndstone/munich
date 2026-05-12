<?php

namespace App\Repository;

use App\Entity\RockTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RockTranslation>
 *
 * @method RockTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method RockTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method RockTranslation[]    findAll()
 * @method RockTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RockTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RockTranslation::class);
    }

    //    /**
    //     * @return RockTranslation[] Returns an array of RockTranslation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RockTranslation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
