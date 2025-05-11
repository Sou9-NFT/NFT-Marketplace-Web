<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ArtworkRepository;
use App\Repository\BetSessionRepository;
use App\Repository\CategoryRepository;
use App\Repository\BlogRepository;
use App\Repository\RaffleRepository;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    private $httpClient;
    private $etherscanApiUrl;
    private $etherscanApiKey;
    private $contractAddress;

    public function __construct(
        HttpClientInterface $httpClient,
        string $etherscanApiUrl = null,
        string $etherscanApiKey = null,
        string $contractAddress = null
    ) {
        $this->httpClient = $httpClient;
        $this->etherscanApiUrl = $etherscanApiUrl ?? $_ENV['ETHERSCAN_API_URL'];
        $this->etherscanApiKey = $etherscanApiKey ?? $_ENV['ETHERSCAN_API_KEY'];
        $this->contractAddress = $contractAddress ?? $_ENV['ETHERSCAN_CONTRACT_ADDRESS'];
    }

    private function getTokenSupply(): float
    {
        try {
            $response = $this->httpClient->request('GET', $this->etherscanApiUrl, [
                'query' => [
                    'module' => 'stats',
                    'action' => 'tokensupply',
                    'contractaddress' => $this->contractAddress,
                    'apikey' => $this->etherscanApiKey
                ]
            ]);

            $data = json_decode($response->getContent(), true);
            if ($data['status'] === '1' && isset($data['result'])) {
                return floatval($data['result']) / pow(10, 18);
            }
        } catch (\Exception $e) {
            // Log error and return fallback value
        }

        return 1000000;
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ArtworkRepository $artworkRepository,
        BetSessionRepository $betSessionRepository,
        CategoryRepository $categoryRepository,
        BlogRepository $blogRepository,
        RaffleRepository $raffleRepository,
        ParticipantRepository $participantRepository
    ): Response {
        $totalCirculation = $this->getTokenSupply();
        
        // Get blog analytics
        $blogAnalytics = $blogRepository->getBlogAnalytics();
        
        // Get raffle statistics
        $raffles = $raffleRepository->findAll();
        $totalRaffles = count($raffles);
        $totalParticipants = $participantRepository->count([]);
        
        // Get recent winners (last 30 days)
        $recentWinners = [];
        $thirtyDaysAgo = new \DateTime('-30 days');
        
        foreach ($raffles as $raffle) {
            if ($raffle->getDrawDate() > $thirtyDaysAgo && $raffle->getWinner()) {
                $recentWinners[] = [
                    'raffle_id' => $raffle->getId(),
                    'artwork_title' => $raffle->getArtwork() ? $raffle->getArtwork()->getTitle() : 'Unknown Artwork',
                    'winner_name' => $raffle->getWinner() ? $raffle->getWinner()->getName() : 'Unknown Winner',
                    'draw_date' => $raffle->getDrawDate(),
                    'prize_value' => $raffle->getPrizeValue()
                ];
            }
        }
        
        // Sort winners by most recent draw date
        usort($recentWinners, function ($a, $b) {
            return $b['draw_date'] <=> $a['draw_date'];
        });

        $dashboard_data = [
            'users' => $userRepository->findAll(),
            'artworks' => $artworkRepository->findAll(),
            'recent_bets' => $betSessionRepository->findBy([], ['id' => 'DESC'], 5),
            'categories' => $categoryRepository->findAll(),
            'total_circulation' => $totalCirculation,
            'contract_address' => $this->contractAddress,
            'fee_wallet' => '0x1234567890123456789012345678901234567890',
            'blog_analytics' => $blogAnalytics,
            'raffle_stats' => [
                'total_raffles' => $totalRaffles,
                'total_participants' => $totalParticipants,
                'recent_winners' => $recentWinners
            ]
        ];

        return $this->render('admin/dashboard.html.twig', [
            'dashboard_data' => $dashboard_data
        ]);
    }
}