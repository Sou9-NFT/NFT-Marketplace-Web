<?php

namespace App\Controller\BackOffice;

use App\Entity\User;
use App\Entity\Artwork;
use App\Entity\Category;
use App\Entity\BetSession;
use App\Repository\UserRepository;
use App\Repository\ArtworkRepository;
use App\Repository\CategoryRepository;
use App\Repository\BetSessionRepository;
use App\Repository\BidRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ArtworkRepository $artworkRepository,
        CategoryRepository $categoryRepository,
        BetSessionRepository $betSessionRepository
    ): Response {
        // Fetch all users
        $users = $userRepository->findAll();
        
        // Fetch all artworks
        $artworks = $artworkRepository->findAll();
        
        // Fetch all categories
        $categories = $categoryRepository->findAll();
        
        // Fetch recent bet sessions
        $recentBets = $betSessionRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        // Calculate total circulation - sum of all user balances
        $totalCirculation = 0;
        foreach ($users as $user) {
            $totalCirculation += $user->getBalance();
        }
        
        // Get contract data from crypto_token.txt
        $tokenInfo = file_get_contents($this->getParameter('kernel.project_dir') . '/crypto_token.txt');
        
        // Extract contract address and fee wallet from the token info
        preg_match('/contract address:\s*(.+)$/m', $tokenInfo, $contractMatches);
        preg_match('/fee wallet:(.+)$/m', $tokenInfo, $feeMatches);
        
        $contractAddress = trim($contractMatches[1] ?? '0x44Ab62a8DFC2d8403E27F4b85717Cc3b986d1801');
        $feeWallet = trim($feeMatches[1] ?? '0xC0A7a0BBfB08bF27E5B519f01eF67090Ad28eE5E');

        // Render the base template with dashboard data
        return $this->render('base_back.html.twig', [
            'dashboard_data' => [
                'users' => $users,
                'artworks' => $artworks,
                'categories' => $categories,
                'recent_bets' => $recentBets,
                'total_circulation' => $totalCirculation,
                'contract_address' => $contractAddress,
                'fee_wallet' => $feeWallet,
            ],
            'show_dashboard' => true
        ]);
    }
}