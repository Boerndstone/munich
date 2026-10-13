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
use App\Entity\RockTranslation;
use App\Entity\Routes;
use App\Entity\Topo;
use App\Entity\Videos;
use Doctrine\ORM\QueryBuilder;

/**
 * API Platform resources are shared with the admin application, so the public
 * API must enforce the same visibility rules as the former Symfony frontend.
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
                ->leftJoin(sprintf('%s.belongsToArea', $root), 'public_photo_area')
                ->leftJoin(sprintf('%s.belongsToRock', $root), 'public_photo_rock')
                ->leftJoin(sprintf('%s.belongsToRoute', $root), 'public_photo_route')
                ->leftJoin('public_photo_route.rock', 'public_photo_route_rock')
                ->andWhere(sprintf('%s.status = :approved_photo_status', $root))
                ->andWhere('(public_photo_area.id IS NULL OR public_photo_area.online = :public_area_online)')
                ->andWhere('(public_photo_rock.id IS NULL OR public_photo_rock.online = :public_online)')
                ->andWhere('(public_photo_route.id IS NULL OR public_photo_route_rock.online = :public_online)')
                ->setParameter('approved_photo_status', 'approved')
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1),
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
                ->innerJoin(sprintf('%s.area', $root), 'public_route_area')
                ->andWhere('public_route_rock.online = :public_online')
                ->andWhere('public_route_area.online = :public_area_online')
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1),
            Comment::class => $queryBuilder
                ->innerJoin(sprintf('%s.route', $root), 'public_comment_route')
                ->innerJoin('public_comment_route.rock', 'public_comment_rock')
                ->innerJoin('public_comment_route.area', 'public_comment_area')
                ->andWhere('public_comment_rock.online = :public_online')
                ->andWhere('public_comment_area.online = :public_area_online')
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1),
            RockTranslation::class => $queryBuilder
                ->innerJoin(sprintf('%s.rock', $root), 'public_translation_rock')
                ->innerJoin('public_translation_rock.area', 'public_translation_area')
                ->andWhere('public_translation_rock.online = :public_online')
                ->andWhere('public_translation_area.online = :public_area_online')
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1),
            Videos::class => $queryBuilder
                ->leftJoin(sprintf('%s.videoArea', $root), 'public_video_area')
                ->leftJoin(sprintf('%s.videoRocks', $root), 'public_video_rock')
                ->leftJoin(sprintf('%s.videoRoutes', $root), 'public_video_route')
                ->leftJoin('public_video_route.rock', 'public_video_route_rock')
                ->andWhere('(public_video_area.id IS NULL OR public_video_area.online = :public_area_online)')
                ->andWhere('(public_video_rock.id IS NULL OR public_video_rock.online = :public_online)')
                ->andWhere('(public_video_route.id IS NULL OR public_video_route_rock.online = :public_online)')
                ->setParameter('public_online', true)
                ->setParameter('public_area_online', 1),
            default => null,
        };
    }
}
