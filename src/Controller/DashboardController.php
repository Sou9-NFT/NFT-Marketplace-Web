<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ArtworkRepository;
use App\Repository\BetSessionRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ArtworkRepository $artworkRepository,
        BetSessionRepository $betSessionRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $dashboard_data = [
            'users' => $userRepository->findAll(),
            'artworks' => $artworkRepository->findAll(),
            'recent_bets' => $betSessionRepository->findBy([], ['id' => 'DESC'], 5),
            'categories' => $categoryRepository->findAll(),
            'total_circulation' => 1000000, // Example value, adjust as needed
            'contract_address' => '0x44Ab62a8DFC2d8403E27F4b85717Cc3b986d1801',
            'fee_wallet' => '0x1234567890123456789012345678901234567890', // Replace with actual fee wallet
        ];

        return $this->render('admin/dashboard.html.twig', [
            'dashboard_data' => $dashboard_data
        ]);
    }
}