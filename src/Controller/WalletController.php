<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class WalletController extends AbstractController
{
    #[Route('/connect-wallet', name: 'app_connect_wallet', methods: ['POST'])]
    public function connectWallet(
        Request $request, 
        Security $security,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $walletAddress = $data['address'] ?? null;

        if (!$walletAddress) {
            return new JsonResponse(['error' => 'Wallet address is required'], 400);
        }

        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Update the user's wallet address
        $user->setWalletAddress($walletAddress);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'address' => $walletAddress
        ]);
    }

    #[Route('/wallet-status', name: 'app_wallet_status', methods: ['GET'])]
    public function getWalletStatus(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'connected' => false,
                'address' => null
            ]);
        }

        return new JsonResponse([
            'connected' => $user->getWalletAddress() !== null,
            'address' => $user->getWalletAddress()
        ]);
    }
}