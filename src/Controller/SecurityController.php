<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            // If user is already logged in and has admin role, allow access to back office
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_home_page_back');
            }
            // Otherwise redirect to front office
            return $this->redirectToRoute('app_home_page');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/admin/login', name: 'admin_login')]
    public function backLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            // If user is already logged in and has admin role, allow access to back office
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_home_page_back');
            }
            // If user is logged in but doesn't have admin role, show access denied
            return $this->redirectToRoute('app_access_denied');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/admin_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/access-denied', name: 'app_access_denied')]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
