<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;

class ProfilerToolbarAdminOnlySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ?WebDebugToolbarListener $debugToolbar,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->debugToolbar === null) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        $isAdminPath = str_starts_with($path, '/admin');
        $isProfilerPath = str_starts_with($path, '/_wdt') || str_starts_with($path, '/_profiler');

        if ($isAdminPath || $isProfilerPath) {
            $this->debugToolbar?->setMode(WebDebugToolbarListener::ENABLED);

            return;
        }

        $this->debugToolbar?->setMode(WebDebugToolbarListener::DISABLED);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }
}
