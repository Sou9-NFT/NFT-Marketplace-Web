<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $notifications = [];
        
        // Check if user is authenticated
        if ($this->getUser()) {
            // Fetch notifications for the current user
            $notifications = $notificationRepository->findBy(
                ['receiver' => $this->getUser()],
                ['time' => 'DESC']
            );
        }
        
        return $this->render('home_page/index.html.twig', [
            'controller_name' => 'HomePageController',
            'notifications' => $notifications,
        ]);
    }

    #[Route('/admin', name: 'app_home_page_back')]
    public function indexBack(): Response
    {
        return $this->render('home_page/index_back.html.twig', [
            'controller_name' => 'HomePageController',
        ]);
    }
}
