<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PhotoUploadController extends AbstractController
{
    #[Route('/upload-photo', name: 'upload_photo', methods: ['GET', 'POST'], defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/Foto-hochladen', name: 'upload_photo_de', methods: ['GET', 'POST'], defaults: ['_locale' => 'de'], priority: 350)]
    #[Route('/en/upload-photo', name: 'upload_photo_en', methods: ['GET', 'POST'], defaults: ['_locale' => 'en'], priority: 350)]
    public function upload(Request $request): Response
    {
        $route = 'en' === $request->getLocale() ? 'index_en' : 'index';

        return $this->redirectToRoute($route, [], Response::HTTP_MOVED_PERMANENTLY);
    }
}
