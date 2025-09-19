<?php

namespace App\Repository;

use App\Entity\FirstAscencionist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FirstAscencionist>
 *
 * @method FirstAscencionist|null find($id, $lockMode = null, $lockVersion = null)
 * @method FirstAscencionist|null findOneBy(array $criteria, array $orderBy = null)
 * @method FirstAscencionist[]    findAll()
 * @method FirstAscencionist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FirstAscencionistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FirstAscencionist::class);
    }

    public function save(FirstAscencionist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FirstAscencionist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return FirstAscencionist[] Returns an array of FirstAscencionist objects
     */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.firstName LIKE :name OR f.lastName LIKE :name OR CONCAT(f.firstName, \' \', f.lastName) LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('f.lastName', 'ASC')
            ->addOrderBy('f.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return FirstAscencionist[] Returns an array of FirstAscencionist objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FirstAscencionist
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
