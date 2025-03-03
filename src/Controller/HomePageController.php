<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\BetSessionRepository;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(NotificationRepository $notificationRepository , BetSessionRepository $betSessionRepository): Response
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

        $activeBetSessions = $betSessionRepository->createQueryBuilder('b')
        ->where('b.status = :status')
        ->setParameter('status', 'active')
        ->setMaxResults(6)
        ->getQuery()
        ->getResult();
        
        return $this->render('home_page/index.html.twig', [
            'live_bet_sessions' => $activeBetSessions,
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
