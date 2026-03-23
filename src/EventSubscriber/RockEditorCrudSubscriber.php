<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Rock;
use App\Entity\Routes;
use App\Entity\Topo;
use App\Entity\User;
use App\Entity\Videos;
use App\Service\RockAccessService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class RockEditorCrudSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RockAccessService $rockAccessService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [BeforeCrudActionEvent::class => 'onBeforeCrudAction'];
    }

    public function onBeforeCrudAction(BeforeCrudActionEvent $event): void
    {
        $context = $event->getAdminContext();
        if (null === $context || null === $context->getCrud()) {
            return;
        }

        $user = $context->getUser();
        if (!$user instanceof User || !$this->rockAccessService->isRockScoped($user)) {
            return;
        }

        $fqcn = $context->getCrud()->getEntityFqcn();
        if (!\in_array($fqcn, [Rock::class, Routes::class, Topo::class, Videos::class], true)) {
            return;
        }

        $page = $context->getCrud()->getCurrentPage();

        if (Crud::PAGE_INDEX === $page) {
            return;
        }

        if (Rock::class === $fqcn && Crud::PAGE_NEW === $page) {
            throw new AccessDeniedHttpException('Rock editors cannot create new crags.');
        }

        if (Routes::class === $fqcn && Crud::PAGE_NEW === $page) {
            $request = $context->getRequest();
            $rockId = $request->query->get('rockId') ?? $request->query->get('rock');
            if (null !== $rockId && '' !== $rockId) {
                $rock = $this->entityManager->getRepository(Rock::class)->find((int) $rockId);
                if (!$this->rockAccessService->canEditRock($user, $rock)) {
                    throw new AccessDeniedHttpException('You cannot add routes for this crag.');
                }
            }

            return;
        }

        $instance = $context->getEntity()->getInstance();
        if (null === $instance) {
            return;
        }

        $allowed = match (true) {
            $instance instanceof Rock => $this->rockAccessService->canEditRock($user, $instance),
            $instance instanceof Routes => $this->rockAccessService->canEditRoute($user, $instance),
            $instance instanceof Topo => $this->rockAccessService->canEditTopo($user, $instance),
            $instance instanceof Videos => $this->rockAccessService->canEditVideo($user, $instance),
            default => true,
        };

        if (!$allowed) {
            throw new AccessDeniedHttpException('You do not have access to this record.');
        }
    }
}
