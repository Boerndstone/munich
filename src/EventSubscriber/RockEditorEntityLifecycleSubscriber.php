<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Comment;
use App\Entity\Rock;
use App\Entity\Routes;
use App\Entity\Topo;
use App\Entity\User;
use App\Entity\Videos;
use App\Service\RockAccessService;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RockEditorEntityLifecycleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RockAccessService $rockAccessService,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'onBeforePersist',
            BeforeEntityUpdatedEvent::class => 'onBeforeUpdate',
            BeforeEntityDeletedEvent::class => 'onBeforeDelete',
        ];
    }

    public function onBeforePersist(BeforeEntityPersistedEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->rockAccessService->isRockScoped($user)) {
            return;
        }

        $entity = $event->getEntityInstance();
        match (true) {
            $entity instanceof Rock => throw new AccessDeniedException('Rock editors cannot create new crags.'),
            $entity instanceof Routes => $this->assertCanEditRoute($user, $entity),
            $entity instanceof Topo => $this->assertCanEditTopo($user, $entity),
            $entity instanceof Videos => $this->assertCanEditVideo($user, $entity),
            $entity instanceof Comment => $this->assertCanModerateComment($user, $entity),
            default => null,
        };
    }

    public function onBeforeUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->rockAccessService->isRockScoped($user)) {
            return;
        }

        $entity = $event->getEntityInstance();
        match (true) {
            $entity instanceof Rock => $this->assertCanEditRock($user, $entity),
            $entity instanceof Routes => $this->assertCanEditRoute($user, $entity),
            $entity instanceof Topo => $this->assertCanEditTopo($user, $entity),
            $entity instanceof Videos => $this->assertCanEditVideo($user, $entity),
            $entity instanceof Comment => $this->assertCanModerateComment($user, $entity),
            default => null,
        };
    }

    public function onBeforeDelete(BeforeEntityDeletedEvent $event): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->rockAccessService->isRockScoped($user)) {
            return;
        }

        $entity = $event->getEntityInstance();
        match (true) {
            $entity instanceof Rock => throw new AccessDeniedException('Rock editors cannot delete crags.'),
            $entity instanceof Routes => $this->assertCanEditRoute($user, $entity),
            $entity instanceof Topo => $this->assertCanEditTopo($user, $entity),
            $entity instanceof Videos => $this->assertCanEditVideo($user, $entity),
            $entity instanceof Comment => $this->assertCanModerateComment($user, $entity),
            default => null,
        };
    }

    private function assertCanModerateComment(User $user, Comment $comment): void
    {
        if (!$this->rockAccessService->canModerateComment($user, $comment)) {
            throw new AccessDeniedException('You cannot moderate this comment.');
        }
    }

    private function assertCanEditRock(User $user, Rock $rock): void
    {
        if (!$this->rockAccessService->canEditRock($user, $rock)) {
            throw new AccessDeniedException('You cannot modify this crag.');
        }
    }

    private function assertCanEditRoute(User $user, Routes $route): void
    {
        if (!$this->rockAccessService->canEditRoute($user, $route)) {
            throw new AccessDeniedException('You cannot modify this route.');
        }
    }

    private function assertCanEditTopo(User $user, Topo $topo): void
    {
        if (!$this->rockAccessService->canEditTopo($user, $topo)) {
            throw new AccessDeniedException('You cannot modify this topo.');
        }
    }

    private function assertCanEditVideo(User $user, Videos $video): void
    {
        if (!$this->rockAccessService->canEditVideo($user, $video)) {
            throw new AccessDeniedException('You cannot modify this video.');
        }
    }
}
