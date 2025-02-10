<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateItemController extends AbstractController
{
    #[Route('/create', name: 'app_create_item')]
    public function index(): Response
    {
        return $this->render('create_item/index.html.twig', [
            'controller_name' => 'CreateItemController',
        ]);
    }
}
