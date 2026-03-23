<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Comment;
use App\Entity\Rock;
use App\Entity\Routes;
use App\Entity\Topo;
use App\Entity\User;
use App\Entity\Videos;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;

final class RockAccessService
{
    /**
     * Scoped rock editors: ROLE_ROCK_EDITOR without ROLE_SUPER_ADMIN.
     * Full admins without ROLE_ROCK_EDITOR are not scoped (see {@see getEditableRockIds()}).
     */
    public function isRockScoped(UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return \in_array('ROLE_ROCK_EDITOR', $user->getRoles(), true)
            && !$this->bypassesRockScope($user);
    }

    public function bypassesRockScope(UserInterface $user): bool
    {
        if (!$user instanceof User) {
            return true;
        }

        return \in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * @return list<int>|null null means unrestricted (all rocks)
     */
    public function getEditableRockIds(User $user): ?array
    {
        if ($this->bypassesRockScope($user)) {
            return null;
        }

        if (!$this->isRockScoped($user)) {
            return null;
        }

        $ids = [];
        foreach ($user->getEditableRocks() as $rock) {
            $id = $rock->getId();
            if (null !== $id) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public function canEditRock(?UserInterface $user, ?Rock $rock): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return true;
        }

        if ([] === $ids || null === $rock || null === $rock->getId()) {
            return false;
        }

        return \in_array($rock->getId(), $ids, true);
    }

    public function canEditRoute(?UserInterface $user, ?Routes $route): bool
    {
        if (null === $route) {
            return false;
        }

        return $this->canEditRock($user, $route->getRock());
    }

    /**
     * Route comments in admin: allowed when the comment’s route belongs to an editable rock.
     */
    public function canModerateComment(?UserInterface $user, ?Comment $comment): bool
    {
        if (null === $comment) {
            return false;
        }

        return $this->canEditRoute($user, $comment->getRoute());
    }

    public function canEditTopo(?UserInterface $user, ?Topo $topo): bool
    {
        if (null === $topo) {
            return false;
        }

        return $this->canEditRock($user, $topo->getRocks());
    }

    public function canEditVideo(?UserInterface $user, ?Videos $video): bool
    {
        if (null === $video || !$user instanceof User) {
            return false;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return true;
        }

        if ([] === $ids) {
            return false;
        }

        $rock = $video->getVideoRocks();
        $route = $video->getVideoRoutes();

        if (null === $rock && null === $route) {
            return false;
        }

        if (null !== $rock && !$this->canEditRock($user, $rock)) {
            return false;
        }

        if (null !== $route && !$this->canEditRoute($user, $route)) {
            return false;
        }

        return true;
    }

    /**
     * Restrict EasyAdmin index/autocomplete queries to editable rocks (root alias must be "entity").
     */
    public function restrictRockQueryBuilder(QueryBuilder $qb, ?UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb->andWhere('entity.id IN (:editableRockIds)')
            ->setParameter('editableRockIds', $ids);
    }

    public function restrictRoutesQueryBuilder(QueryBuilder $qb, ?UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb->andWhere('IDENTITY(entity.rock) IN (:editableRockIds)')
            ->setParameter('editableRockIds', $ids);
    }

    public function restrictTopoQueryBuilder(QueryBuilder $qb, ?UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $qb->andWhere('IDENTITY(entity.rocks) IN (:editableRockIds)')
            ->setParameter('editableRockIds', $ids);
    }

    public function restrictVideosQueryBuilder(QueryBuilder $qb, ?UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $routesFqcn = Routes::class;
        $qb->andWhere(
            '(entity.videoRocks IS NOT NULL AND IDENTITY(entity.videoRocks) IN (:editableRockIds))'
            .' OR (entity.videoRoutes IS NOT NULL AND IDENTITY(entity.videoRoutes) IN (SELECT vr.id FROM '.$routesFqcn.' vr WHERE IDENTITY(vr.rock) IN (:editableRockIds)))'
        )->setParameter('editableRockIds', $ids);
    }

    public function restrictCommentsQueryBuilder(QueryBuilder $qb, ?UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $ids = $this->getEditableRockIds($user);
        if (null === $ids) {
            return;
        }

        if ([] === $ids) {
            $qb->andWhere('1 = 0');

            return;
        }

        $routesFqcn = Routes::class;
        $qb->andWhere(
            'entity.route IS NOT NULL AND IDENTITY(entity.route) IN (SELECT r.id FROM '.$routesFqcn.' r WHERE IDENTITY(r.rock) IN (:editableRockIds))'
        )->setParameter('editableRockIds', $ids);
    }
}
