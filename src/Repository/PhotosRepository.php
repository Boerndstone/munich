<?php

namespace App\Repository;

use App\Entity\Photos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Photos>
 *
 * @method Photos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Photos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Photos[]    findAll()
 * @method Photos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhotosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photos::class);
    }

    public function save(Photos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Photos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Photos[] Returns an array of Photos objects
     */
    public function findPhotosForRock($rock): array
    {
        return $this->createQueryBuilder('photo')
            ->andWhere('photo.belongsToRock = :rock')
            ->andWhere('photo.status = :status')
            ->setParameter('rock', $rock)
            ->setParameter('status', 'approved')
            ->orderBy('photo.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Photos[] Returns an array of pending Photos objects
     */
    public function findPendingPhotos(): array
    {
        return $this->createQueryBuilder('photo')
            ->andWhere('photo.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('photo.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
