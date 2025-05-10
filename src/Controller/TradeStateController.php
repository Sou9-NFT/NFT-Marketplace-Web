<?php

namespace App\Controller;


use App\Entity\TradeState;
use App\Entity\Artwork;
use App\Entity\TradeOffer;
use App\Entity\Notification;

use App\Repository\TradeStateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/trade')]
class TradeStateController extends AbstractController
{
    #[Route('/state', name: 'app_trade_state_index', methods: ['GET'])]
public function index(Request $request, TradeStateRepository $tradeStateRepository): Response
{
    $senderSearch = $request->query->get('sender_search', ''); // Get sender search term
    $receiverSearch = $request->query->get('receiver_search', ''); // Get receiver search term

    // Build the query with filtering for both sender and receiver names
    $tradeStatesQuery = $tradeStateRepository->createQueryBuilder('t')
        ->leftJoin('t.sender', 's') // Join with sender entity
        ->leftJoin('t.receiver', 'r') // Join with receiver entity
        ->where('t.receiver = :user') // Ensure the receiver is the logged-in user
        ->andWhere('(s.name LIKE :senderSearch OR :senderSearch = \'\')') // Filter by sender's name
        ->andWhere('(r.name LIKE :receiverSearch OR :receiverSearch = \'\')') // Filter by receiver's name
        ->setParameter('user', $this->getUser()) // Filter by current logged-in user
        ->setParameter('senderSearch', '%' . $senderSearch . '%') // Apply search filter for sender
        ->setParameter('receiverSearch', '%' . $receiverSearch . '%') // Apply search filter for receiver
        ->getQuery();

    $tradeStates = $tradeStatesQuery->getResult(); // Fetch the filtered trade states

    return $this->render('trade_state/index.html.twig', [
        'trade_states' => $tradeStates,
        'sender_search' => $senderSearch, // Pass the sender search term
        'receiver_search' => $receiverSearch, // Pass the receiver search term
    ]);
}




    #[Route('/state/{id}', name: 'app_trade_state_show', methods: ['GET'])]
    public function show(TradeState $tradeState): Response
    {
        return $this->render('trade_state/viewtradestate.html.twig', [
            'trade_state' => $tradeState,
        ]);
    }

    #[Route('/trade/state/accept/{id}', name: 'app_trade_state_accept', methods: ['POST'])]
public function acceptTrade(int $id, EntityManagerInterface $entityManager): Response
{
    // Fetch the trade state by ID
    $tradeState = $entityManager->getRepository(TradeState::class)->find($id);
    
    if (!$tradeState) {
        $this->addFlash('error', 'Trade state not found.');
        return $this->redirectToRoute('app_trade_offer_index');
    }

    // Ensure the current user is either the sender or the receiver
    $user = $this->getUser();
    if ($tradeState->getSender() !== $user && $tradeState->getReceiver() !== $user) {
        throw $this->createAccessDeniedException('You are not involved in this trade.');
    }

    // Swap the ownership of the offered and received items
    $receivedItem = $tradeState->getReceivedItem();
    $offeredItem = $tradeState->getOfferedItem();

    // Swap the ownership
    $a = $offeredItem->getOwner();
    $offeredItem->setOwner($tradeState->getReceiver());
    $receivedItem->setOwner($a);

    // Persist the changes for the items
    $entityManager->persist($receivedItem);
    $entityManager->persist($offeredItem);
    $entityManager->flush();

    // Update the trade offer status
    $tradeOffer = $tradeState->getTradeOffer();
    if ($tradeOffer) {
        $tradeOffer->setStatus('Accepted');
        $entityManager->persist($tradeOffer);
    }

    // Create and send notifications
    $sender = $tradeState->getSender();
    $receiver = $tradeState->getReceiver();

    // Notification for the receiver
    $notificationMessage = 'Your trade offer has been Accepted.';
    $notificationReceiver = new Notification();
    $notificationReceiver->setReceiver($receiver);
    $notificationReceiver->setTitle($notificationMessage);
    $notificationReceiver->setType('trade_accepted');
    $notificationReceiver->setCreatedAt(new \DateTimeImmutable());
    $entityManager->persist($notificationReceiver);

    // Notification for the sender
    $notificationMessageSender = 'Your trade offer for ' . $offeredItem->getTitle() . ' has been accepted!';
    $notificationSender = new Notification();
    $notificationSender->setReceiver($sender);
    $notificationSender->setTitle($notificationMessageSender);
    $notificationSender->setType('trade_accepted');
    $notificationSender->setCreatedAt(new \DateTimeImmutable());
    $entityManager->persist($notificationSender);

    // Fetch and update the existing notification related to this tradeState
    $existingNotification = $entityManager->getRepository(Notification::class)->findOneBy([
        'tradeState' => $tradeState,
    ]);
    if ($existingNotification) {
        // Update the notification type to 'trade_accepted'
        $existingNotification->setType('trade_accepted');
        $entityManager->persist($existingNotification);
    }

    $entityManager->flush();

    // Add success flash message
    $this->addFlash('success', 'Trade accepted and items swapped.');

    return $this->redirectToRoute('app_trade_offer_index');
}


#[Route('/trade/state/reject/{id}', name: 'app_trade_state_reject', methods: ['POST'])]
public function rejectTrade(int $id, EntityManagerInterface $entityManager): Response
{
    // Fetch the trade state by ID
    $tradeState = $entityManager->getRepository(TradeState::class)->find($id);
    
    if (!$tradeState) {
        $this->addFlash('error', 'Trade state not found.');
        return $this->redirectToRoute('app_trade_offer_index');
    }

    // Ensure the current user is either the sender or the receiver
    $user = $this->getUser();
    if ($tradeState->getSender() !== $user && $tradeState->getReceiver() !== $user) {
        throw $this->createAccessDeniedException('You are not involved in this trade.');
    }

    // Fetch the associated trade offer and change its status to 'rejected'
    $tradeOffer = $tradeState->getTradeOffer();
    if ($tradeOffer) {
        $tradeOffer->setStatus('Rejected');
        $entityManager->persist($tradeOffer);
        
        // Create notification for trade rejection
        $sender = $tradeState->getSender();
        $receiver = $tradeState->getReceiver();
        $notificationMessageReceiver = 'Your trade offer for "' . $tradeState->getOfferedItem()->getTitle() . '" has been rejected.';
        
        // If current user is sender, notify receiver
        $notificationReceiver = new Notification();
        $notificationReceiver->setReceiver($receiver);
        $notificationReceiver->setTitle($notificationMessageReceiver);
        $notificationReceiver->setType('trade_rejected');
        $notificationReceiver->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($notificationReceiver);

        // Create notification for the sender
        $notificationMessageSender = 'Your trade offer for "' . $tradeState->getOfferedItem()->getTitle() . '" has been rejected.';
        $notificationSender = new Notification();
        $notificationSender->setReceiver($sender);
        $notificationSender->setTitle($notificationMessageSender);
        $notificationSender->setType('trade_rejected');
        $notificationSender->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($notificationSender);
        
        $entityManager->flush();
    }

    $existingNotification = $entityManager->getRepository(Notification::class)->findOneBy([
        'tradeState' => $tradeState,
    ]);
    if ($existingNotification) {
        // Update the notification type to 'trade_rejected'
        $existingNotification->setType('trade_rejected');
        $entityManager->persist($existingNotification);
    }
    $entityManager->flush();

    // Add success flash message
    $this->addFlash('success', 'Trade rejected.');

    return $this->redirectToRoute('app_trade_offer_index');
}


}
