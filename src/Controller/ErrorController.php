<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ErrorController extends AbstractController
{
   /*
    #[Route('/{url}', name: 'app_route_not_found', requirements: ['url' => '.+'])]
    public function routeNotFound(): Response
    {
        return $this->render('error/index.html.twig');
    }
       */
}
