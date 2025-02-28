<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class OAuthController extends AbstractController
{
    #[Route('/connect/github', name: 'connect_github')]
    public function connectGithub(ClientRegistry $clientRegistry): Response
    {
        try {
            return $clientRegistry->getClient('github')->redirect(['user:email']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to connect with GitHub. Please try again.');
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function connectGithubCheck(): Response
    {
        return new Response('Connecting...');
    }
}