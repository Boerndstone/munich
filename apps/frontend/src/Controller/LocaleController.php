<?php
// src/Controller/LocaleController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    /**
     * @Route("/change-locale/{locale}", name="change_locale")
     */
    public function changeLocale(Request $request, string $locale): Response
    {
        // Set the locale in the session
        $request->getSession()->set('_locale', $locale);

        // Redirect to the previous page
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }
}
