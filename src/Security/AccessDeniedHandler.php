<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // Check if the request is for back office
        $isBackOffice = str_contains($request->getPathInfo(), '/admin');
        
        if ($isBackOffice && !$this->security->isGranted('ROLE_ADMIN')) {
            // If user tries to access back office without admin role, show access denied page
            return new RedirectResponse($this->urlGenerator->generate('app_access_denied'));
        }
        
        // For other cases, redirect to login
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
