<?php

namespace App\Controller;

use App\Service\EtherscanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserWalletController extends AbstractController
{
    private $etherscanService;

    public function __construct(EtherscanService $etherscanService)
    {
        $this->etherscanService = $etherscanService;
    }

    #[Route('/wallet-connect', name: 'app_wallet_connect')]
    public function walletConnect(): Response
    {
        return $this->render('user/wallet-connect.html.twig');
    }

    #[Route('/user/wallet/update', name: 'app_user_update_wallet', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateWalletAddress(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $walletAddress = $data['walletAddress'] ?? null;

        if (!$walletAddress) {
            return new JsonResponse(['error' => 'Wallet address is required'], 400);
        }

        try {
            $user = $this->getUser();
            $user->setWalletAddress($walletAddress);
            
            // Get and update balance from blockchain
            $blockchainBalance = $this->etherscanService->getTokenBalance($walletAddress);
            $user->setBalance($blockchainBalance);
            
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'balance' => $blockchainBalance
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}