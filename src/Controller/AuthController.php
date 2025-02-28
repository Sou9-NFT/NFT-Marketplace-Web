<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordResetRequestFormType;
use App\Form\PasswordResetFormType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home_page');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
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
