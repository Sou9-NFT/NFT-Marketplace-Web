<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notification;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(Security $security, EntityManagerInterface $em, Request $request): Response
    {
        $user = $security->getUser(); // Using the Security service to get the current user
    
        // Debugging the user to ensure it is fetched correctly
        dump($user); 
    
        // Check if the user is null
        if (!$user) {
            // Handle the case when the user is not logged in
            return $this->redirectToRoute('app_login'); // Or another appropriate route
        }
    
        // Fetch the user identifier using getUserIdentifier() (which should return the email or username)
        $userId = $user->getUserIdentifier();
    
        // Fetch notifications for the logged-in user where 'isRead' is false
        // Assuming 'receiverName' corresponds to the user identifier (could also be 'receiverId' if you store IDs)
        $notifications = $em->getRepository(Notification::class)
                            ->findBy(['receiverName' => $userId, 'isRead' => false]);
    
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
