<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PasswordResetService
{
    private $mailer;
    private $entityManager;
    private $userProvider;
    private $passwordHasher;
    private $urlGenerator;
    private $requestStack;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserProviderInterface $userProvider,
        UserPasswordHasherInterface $passwordHasher,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
        $this->passwordHasher = $passwordHasher;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function sendPasswordResetEmail(string $email): void
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($email);
        } catch (\Exception $e) {
            throw new UserNotFoundException(sprintf('No user found with email "%s"', $email));
        }

        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($token);
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+1 hour'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $resetUrl = $this->urlGenerator->generate('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@example.com', 'NFT Marketplace'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('security/password_reset_email.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    public function validatePasswordResetToken(string $token): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['passwordResetToken' => $token]);

        if (!$user || $user->getPasswordResetTokenExpiresAt() < new \DateTime()) {
            return null;
        }

        return $user;
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
