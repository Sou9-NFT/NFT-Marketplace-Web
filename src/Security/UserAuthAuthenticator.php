<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    public const ADMIN_LOGIN_ROUTE = 'admin_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $route = $request->attributes->get('_route');
        $user = $token->getUser();
        $isAdminArea = str_starts_with($request->getPathInfo(), '/admin');
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        // Handle admin area login attempts
        if ($isAdminArea) {
            if ($isAdmin) {
                return new RedirectResponse($this->urlGenerator->generate('app_home_page_back'));
            } else {
                return new RedirectResponse($this->urlGenerator->generate('app_access_denied'));
            }
        }

        // For front office, allow both admin and regular users
        return new RedirectResponse($this->urlGenerator->generate('app_home_page'));
    }

    protected function getLoginUrl(Request $request): string
    {
        // If trying to access admin area, use admin login
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return $this->urlGenerator->generate(self::ADMIN_LOGIN_ROUTE);
        }
        
        // Otherwise use regular login
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
