<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
        private Environment $twig
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        // For back office and admin access attempts, show the access denied page
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/admin') || str_starts_with($path, '/back')) {
            return new Response($this->twig->render('security/access_denied.html.twig'), Response::HTTP_FORBIDDEN);
        }
        
        // For front office, redirect to login if not authenticated
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->urlGenerator->generate('app_login'));
        }

        // For other cases, redirect to home page
        return new RedirectResponse($this->urlGenerator->generate('app_home_page'));
    }
}
