<?php

namespace App\Controller;

use App\Form\PasswordResetRequestFormType;
use App\Form\PasswordResetFormType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

        return $this->render('security/back_login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/access-denied', name: 'app_access_denied')]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig');
    }

    #[Route(path: '/reset-password-request', name: 'app_reset_password_request')]
    public function requestPasswordReset(Request $request, PasswordResetService $passwordResetService): Response
    {
        $form = $this->createForm(PasswordResetRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $passwordResetService->sendPasswordResetEmail($email);

            $this->addFlash('success', 'If an account with that email exists, a password reset link has been sent.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset_request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token, PasswordResetService $passwordResetService): Response
    {
        $user = $passwordResetService->validatePasswordResetToken($token);

        if (!$user) {
            $this->addFlash('danger', 'Invalid or expired password reset token.');
            return $this->redirectToRoute('app_reset_password_request');
        }

        $form = $this->createForm(PasswordResetFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordResetService->resetPassword($user, $form->get('plainPassword')->getData());

            $this->addFlash('success', 'Your password has been reset successfully.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
