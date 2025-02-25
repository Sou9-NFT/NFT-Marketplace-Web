<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class GithubController extends AbstractController
{
    #[Route('/connect/github', name: 'connect_github')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('github')
            ->redirect([
                'user:email', // Scopes we need
            ]);
    }

    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function connectCheckAction()
    {
        // This method will be intercepted by the OAuth2 authenticator
    }
}