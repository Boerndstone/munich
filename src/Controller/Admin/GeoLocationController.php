<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GeoLocationController extends AbstractDashboardController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/admin/geolocation', name: 'admin_geolocation')]
    public function index(): Response
    {
        return $this->render('admin/geolocation.html.twig');
    }
}
