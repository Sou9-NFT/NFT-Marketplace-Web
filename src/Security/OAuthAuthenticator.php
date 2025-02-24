<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
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
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class OAuthAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check'
            || $request->attributes->get('_route') === 'connect_github_check'
            || $request->attributes->get('_route') === 'connect_facebook_check';
    }

    public function authenticate(Request $request): Passport
    {
        $route = $request->attributes->get('_route');
        $client = match ($route) {
            'connect_google_check' => 'google',
            'connect_github_check' => 'github',
            'connect_facebook_check' => 'facebook',
            default => throw new \LogicException('Unknown OAuth provider'),
        };

        $oauthClient = $this->clientRegistry->getClient($client);
        
        try {
            $accessToken = $this->fetchAccessToken($oauthClient);
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            throw new AuthenticationException('OAuth authentication failed: ' . $e->getMessage());
        }

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $oauthClient, $client) {
                $oauthUser = $oauthClient->fetchUserFromToken($accessToken);
                
                // Get email from OAuth provider
                $email = $oauthUser->getEmail();
                if (!$email) {
                    throw new AuthenticationException('No email provided by OAuth provider.');
                }

                // Check if user exists
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Update existing user's GitHub info if needed
                    if ($client === 'github') {
                        $existingUser->setGithubUsername($oauthUser->getNickname());
                    }
                    $this->entityManager->flush();
                    return $existingUser;
                }

                // Create new user
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword(bin2hex(random_bytes(16)));
                
                // Set name based on OAuth provider data
                $name = match($client) {
                    'google' => $oauthUser->getFirstName() . ' ' . $oauthUser->getLastName(),
                    'github' => $oauthUser->getName() ?: $oauthUser->getNickname(),
                    'facebook' => $oauthUser->getName(),
                    default => $oauthUser->getEmail(),
                };
                $user->setName($name);
                
                // Set GitHub-specific data
                if ($client === 'github') {
                    $user->setGithubUsername($oauthUser->getNickname());
                    // You can also store the avatar URL if you want
                    $user->setProfilePicture($oauthUser->getAvatar());
                }

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Check if there was an intended destination
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Check if user is admin and trying to access admin area
        $isAdminArea = str_starts_with($request->getPathInfo(), '/admin');
        $isAdmin = in_array('ROLE_ADMIN', $token->getRoleNames());

        if ($isAdminArea) {
            if ($isAdmin) {
                return new RedirectResponse($this->router->generate('app_home_page_back'));
            }
            return new RedirectResponse($this->router->generate('app_access_denied'));
        }

        // Default redirect to homepage
        return new RedirectResponse($this->router->generate('app_home_page'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('app_login'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}