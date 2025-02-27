<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserWalletController extends AbstractController
{
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

        $user = $this->getUser();
        $user->setWalletAddress($walletAddress);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}