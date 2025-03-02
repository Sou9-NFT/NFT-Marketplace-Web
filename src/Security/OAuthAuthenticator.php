<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GithubUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_github_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = 'github';
        $oauthClient = $this->clientRegistry->getClient($client);
        
        try {
            $accessToken = $this->fetchAccessToken($oauthClient);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            throw new AuthenticationException('OAuth authentication failed: ' . $e->getMessage());
        }

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $oauthClient) {
                /** @var GithubUser $githubUser */
                $githubUser = $oauthClient->fetchUserFromToken($accessToken);
                
                $email = $githubUser->getEmail();
                if (!$email) {
                    throw new AuthenticationException('No email provided by GitHub.');
                }

                // Check if user exists
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    $existingUser->setGithubUsername($githubUser->getNickname());
                    $userData = $githubUser->toArray();
                    if (isset($userData['avatar_url'])) {
                        $existingUser->setProfilePicture($userData['avatar_url']);
                    }
                    $this->entityManager->flush();
                    return $existingUser;
                }

                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword(bin2hex(random_bytes(16)));
                $user->setGithubUsername($githubUser->getNickname());
                $user->setName($githubUser->getName() ?? $githubUser->getNickname() ?? explode('@', $email)[0]);
                
                $userData = $githubUser->toArray();
                if (isset($userData['avatar_url'])) {
                    $user->setProfilePicture($userData['avatar_url']);
                }
                
                $user->setCreatedAt(new \DateTimeImmutable());
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_home_page'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}