<?php

namespace App\Controller;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



final class NotificationController extends AbstractController
{
    public function showNotifications(Security $security, EntityManagerInterface $em, Request $request)
    {
        // Get the logged-in user
        $user = $security->getUser(); // Using the Security service to get the current user
    
        // Debugging the user to ensure it is fetched correctly
        dump($user); 
    
        // Check if the user is null
        if (!$user) {
            // Handle the case when the user is not logged in
            return $this->redirectToRoute('login'); // Or another appropriate route
        }
    
        // Fetch the user identifier using getUserIdentifier() (which should return the email or username)
        $userId = $user->getUserIdentifier();
    
        // Fetch notifications for the logged-in user where 'isRead' is false
        // Assuming 'receiverName' corresponds to the user identifier (could also be 'receiverId' if you store IDs)
        $notifications = $em->getRepository(Notification::class)
                            ->findBy(['receiverName' => $userId, 'isRead' => false]);
    
        // Debugging the notifications to see if anything is returned
        dump($notifications); 
    
        // Render the notifications in the dropdown template
        return $this->render('base.html.twig', [
            'notifications' => $notifications,
        ]);
    }
    

    

}
