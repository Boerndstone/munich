<?php

namespace App\Controller;

use App\Repository\PhotosRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class GalleryController extends AbstractController
{
    #[Route('/gallery-data/{rock}', name: 'gallery_data')]
    public function galleryData(PhotosRepository $photosRepository, $rock): JsonResponse
    {
        $galleryItems = $photosRepository->findPhotosForRock($rock);

        // Serialize data to JSON format
        $jsonData = [];
        foreach ($galleryItems as $item) {
            $jsonData[] = [
                'src' => $item->getName(),
                'subHtml' => $item->getDescription(),
            ];
        }

        // Return JSON response
        return $this->json($jsonData);
    }
}
