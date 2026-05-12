<?php

namespace App\Controller;

use App\Repository\AreaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function showError(\Throwable $exception, AreaRepository $areaRepository): Response
    {
        $sideBar = $areaRepository->sidebarNavigation();
        $areas = $areaRepository->getAreasInformation();

        $statusCode = $this->resolveHttpStatus($exception);

        if ($statusCode === 404) {
            return $this->render('error404.html.twig', [
                'areas' => $areas,
                'sideBar' => $sideBar,
            ], new Response('', 404));
        }

        $httpStatus = $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500;

        return $this->render('error.html.twig', [
            'areas' => $areas,
            'sideBar' => $sideBar,
            'status_code' => $httpStatus,
        ], new Response('', $httpStatus));
    }

    private function resolveHttpStatus(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }
        if ($exception instanceof FlattenException) {
            return $exception->getStatusCode();
        }

        return 500;
    }
}
