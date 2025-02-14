<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BetSessionRepository;
#[Route('/back/auctions')]
final class BetSessionBackController extends AbstractController
{
    #[Route("/All",name: 'app_bet_session_index', methods: ['GET'])]
    public function index(BetSessionRepository $betSessionRepository): Response
    {
        return $this->render('bet_session_back/allBetSessions.html.twig', [
            'bet_sessions' => $betSessionRepository->findAll(),
        ]);
    }
    
}
