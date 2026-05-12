<?php

namespace App\Repository;

use App\Entity\Videos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Videos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Videos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Videos[]    findAll()
 * @method Videos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VideosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Videos::class);
    }

    public function findVideosByParams($areaId, $rockId, $routeId)
    {
        return $this->createQueryBuilder('v')
            ->select('v.videoLink')
            ->where('v.videoArea = :areaId')
            ->andWhere('v.videoRocks = :rockId')
            ->andWhere('v.videoRoutes = :routeId')
            ->setParameter('areaId', $areaId)
            ->setParameter('rockId', $rockId)
            ->setParameter('routeId', $routeId)
            ->getQuery()
            ->getResult();
    }
}
