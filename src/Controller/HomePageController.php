<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(): Response
    {
        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
        ]);
    }

    #[Route('/admin', name: 'app_home_page_back')]
    public function indexBack(): Response
    {
        return $this->render('home_page/index_back.html.twig', [
            'controller_name' => 'HomePageController',
        ]);
    }
}
