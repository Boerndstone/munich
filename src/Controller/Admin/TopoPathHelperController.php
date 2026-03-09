<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TopoPathHelperController extends AbstractDashboardController
{
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/admin/topo-path-helper', name: 'admin_topo_path_helper')]
    public function index(): Response
    {
        return $this->render('admin/topo_path_helper.html.twig');
    }
}
