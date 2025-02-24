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
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        try {
            return $clientRegistry->getClient('google')->redirect(['email', 'profile']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to connect with Google. Please try again.');
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        // This method will be handled by the OAuth authenticator
        return new Response('Connecting...');
    }

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

    #[Route('/connect/facebook', name: 'connect_facebook')]
    public function connectFacebook(ClientRegistry $clientRegistry): Response
    {
        try {
            return $clientRegistry->getClient('facebook')->redirect(['email']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to connect with Facebook. Please try again.');
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectFacebookCheck(): Response
    {
        return new Response('Connecting...');
    }
}