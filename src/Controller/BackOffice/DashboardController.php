<?php

namespace App\Controller\BackOffice;

use App\Repository\BetSessionRepository;
use App\Repository\BidRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(BetSessionRepository $betSessionRepository, BidRepository $bidRepository): Response
    {
        $totalBetSessions = $betSessionRepository->count([]);
        $activeBetSessions = $betSessionRepository->count(['status' => 'active']);
        $pendingBetSessions = $betSessionRepository->count(['status' => 'pending']);
        $endedBetSessions = $betSessionRepository->count(['status' => 'ended']);
        $totalBids = $bidRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'totalBetSessions' => $totalBetSessions,
            'activeBetSessions' => $activeBetSessions,
            'pendingBetSessions' => $pendingBetSessions,
            'endedBetSessions' => $endedBetSessions,
            'totalBids' => $totalBids,
        ]);
    }
}