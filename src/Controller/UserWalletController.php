<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserWalletController extends AbstractController
{
    #[Route('/wallet-connect', name: 'app_wallet_connect')]
    public function walletConnect(): Response
    {
        return $this->render('user/wallet-connect.html.twig');
    }
}