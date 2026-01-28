<?php

namespace App\Controller;

use App\Entity\Photos;
use App\Form\PhotoUploadType;
use App\Repository\RockRepository;
use App\Service\ImageProcessingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoUploadController extends AbstractController
{
    #[Route('/upload-photo', name: 'upload_photo')]
    #[Route('/Foto-hochladen', name: 'upload_photo_de', methods: ['GET', 'POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        ImageProcessingService $imageProcessingService
    ): Response {
        $photo = new Photos();
        $photo->setStatus('pending');
        
        $form = $this->createForm(PhotoUploadType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/galerie';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate base filename (without extension)
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $baseFilename = $safeFilename . '-' . uniqid();

                // Move uploaded file to temporary location for processing
                $tempPath = $uploadDir . '/temp_' . $baseFilename . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, basename($tempPath));

                try {
                    // Process image: resize to 1000x563 and create all variants (thumb, 2x, 3x) in WebP
                    $processedFiles = $imageProcessingService->processUploadedImage(
                        $tempPath,
                        $baseFilename,
                        $uploadDir
                    );

                    // Delete temporary original file
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }

                    // Store the main WebP filename in the entity
                    $photo->setName($processedFiles['main']);
                } catch (\Exception $e) {
                    // Clean up temp file on error
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                    
                    $this->addFlash('error', 'Fehler beim Verarbeiten des Bildes: ' . $e->getMessage());
                    return $this->render('frontend/upload_photo.html.twig', [
                        'form' => $form,
                    ]);
                }
            }

            // Set the rock's area if not already set
            if ($photo->getBelongsToRock() && !$photo->getBelongsToArea()) {
                $photo->setBelongsToArea($photo->getBelongsToRock()->getArea());
            }

            $entityManager->persist($photo);
            $entityManager->flush();

            $this->addFlash('success', 'Vielen Dank! Ihr Bild wurde erfolgreich hochgeladen und wartet auf Freigabe durch einen Administrator.');

            return $this->redirectToRoute('upload_photo');
        }

        return $this->render('frontend/upload_photo.html.twig', [
            'form' => $form,
        ]);
    }
}
