<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AuctionsController extends AbstractController
{
    #[Route('/auctions', name: 'app_auctions')]
    public function index(): Response
    {
        return $this->render('auctions/index.html.twig', [
            'controller_name' => 'AuctionsController',
        ]);
    }

    #[Route('/ItemDetails', name: 'app_item_details')]
    public function ItemDetails(): Response
    {
        return $this->render('auctions/ItemDetails.html.twig', [
            'controller_name' => 'AuctionsController',
        ]);
    }
}
