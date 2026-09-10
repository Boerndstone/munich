<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Area;
use App\Entity\Comment;
use App\Entity\Photos;
use App\Entity\Rock;
use App\Entity\Routes;
use App\Entity\Topo;
use Doctrine\ORM\QueryBuilder;

/**
 * Keeps the public API aligned with the public site: unpublished areas/rocks,
 * pending photos and editor-only topos must never be returned by an API call.
 */
final class PublicVisibilityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrictToPublicData($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->restrictToPublicData($queryBuilder, $resourceClass);
    }

    private function restrictToPublicData(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        $root = $queryBuilder->getRootAliases()[0];

        match ($resourceClass) {
            Area::class => $queryBuilder
                ->andWhere(sprintf('%s.online = :public_online', $root))
                ->setParameter('public_online', 1),
            Rock::class => $queryBuilder
                ->andWhere(sprintf('%s.online = :public_online', $root))
                ->setParameter('public_online', true),
            Photos::class => $queryBuilder
                ->andWhere(sprintf('%s.status = :approved_photo_status', $root))
                ->setParameter('approved_photo_status', 'approved'),
            Topo::class => $queryBuilder
                ->innerJoin(sprintf('%s.rocks', $root), 'public_topo_rock')
                ->innerJoin('public_topo_rock.area', 'public_topo_area')
                ->andWhere('public_topo_rock.online = :public_online')
                ->andWhere('public_topo_area.online = :public_area_online')
                ->andWhere(sprintf('%s.withSector = :public_topo_with_sector', $root))
                ->andWhere(sprintf('%s.image IS NOT NULL', $root))
                ->andWhere(sprintf('%s.image <> \'\'', $root))
                ->andWhere(sprintf('%s.pathCollection IS NOT NULL', $root))
                ->andWhere(sprintf('%s.pathCollection <> \'\'', $root))
                ->andWhere(sprintf('%s.updatedAt IS NOT NULL', $root))
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1)
                ->setParameter('public_topo_with_sector', true),
            Routes::class => $queryBuilder
                ->innerJoin(sprintf('%s.rock', $root), 'public_route_rock')
                ->andWhere('public_route_rock.online = :public_online')
                ->setParameter('public_online', true),
            Comment::class => $queryBuilder
                ->innerJoin(sprintf('%s.route', $root), 'public_comment_route')
                ->innerJoin('public_comment_route.rock', 'public_comment_rock')
                ->andWhere('public_comment_rock.online = :public_online')
                ->setParameter('public_online', true),
            default => null,
        };
    }
}
