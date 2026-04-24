<?php
// src/EventSubscriber/LocaleSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct(string $defaultLocale = 'de')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // EasyAdmin / backoffice: always German (public site handles DE/EN separately).
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            $request->setLocale('de');

            return;
        }

        // Prefer locale from the matched route (works without a session, e.g. first visit to /en/...).
        if ($request->attributes->has('_locale')) {
            $request->setLocale((string) $request->attributes->get('_locale'));

            return;
        }

        if (!$request->hasPreviousSession()) {
            return;
        }

        $sessionLocale = $request->getSession()->get('_locale');
        if (\is_string($sessionLocale) && '' !== $sessionLocale) {
            $request->setLocale($sessionLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
