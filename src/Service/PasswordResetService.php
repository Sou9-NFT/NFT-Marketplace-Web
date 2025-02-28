<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PasswordResetService
{
    private $mailer;
    private $entityManager;
    private $userProvider;
    private $passwordEncoder;
    private $urlGenerator;
    private $requestStack;

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        UserProviderInterface $userProvider,
        UserPasswordEncoderInterface $passwordEncoder,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
        $this->passwordEncoder = $passwordEncoder;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function sendPasswordResetEmail(string $email): void
    {
        $user = $this->userProvider->loadUserByUsername($email);

        if (!$user) {
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
            ->htmlTemplate('emails/password_reset.html.twig')
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
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $newPassword);
        $user->setPassword($encodedPassword);
        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiresAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
