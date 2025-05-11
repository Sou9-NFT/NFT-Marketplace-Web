<?php

namespace App\Controller;

use App\Entity\TradeOffer;
use App\Form\TradeOfferType;
use App\Repository\TradeOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
//app_admin_trade
/*return $this->render('back_trade/back_tradeoffer.html.twig', [
            'trade_offers' => $tradeOfferRepository->findAll(),
        ]);*/
#[Route('/admin/trade')]
class BackTradeController extends AbstractController
{
    #[Route(name: 'app_admin_trade', methods: ['GET'])]
public function index(TradeOfferRepository $tradeOfferRepository, Request $request): Response
{
    $user = $this->getUser();
    $searchTerm = $request->query->get('search', ''); // Get the search term from the query string
    $sortOrder = $request->query->get('sort', 'asc'); // Get the sort order from the query string
    $status = $request->query->get('status', ''); // Get the status filter
    $senderSearchTerm = $request->query->get('sender_search', ''); // Get the sender search term from the query string

    // Start building the query
    $queryBuilder = $tradeOfferRepository->createQueryBuilder('t')
        ->leftJoin('t.receiver_name', 'r') // Join User entity via receiver_name relation
        ->leftJoin('t.sender', 's'); // Join User entity for sender

    // Only filter by user for non-admin users
    if (!$this->isGranted('ROLE_ADMIN')) {
        $queryBuilder
            ->where('t.sender = :user')
            ->orWhere('t.receiver_name = :user')
            ->setParameter('user', $user);
    }

    if (!empty($searchTerm)) {
        $queryBuilder->andWhere('r.name LIKE :search')
                     ->setParameter('search', '%' . $searchTerm . '%');
    }

    if (!empty($senderSearchTerm)) {
        $queryBuilder->andWhere('s.name LIKE :sender_search')
                     ->setParameter('sender_search', '%' . $senderSearchTerm . '%');
    }

    // Add the status filter if it's provided
    if ($status) {
        $queryBuilder->andWhere('t.status = :status')
                     ->setParameter('status', $status);
    }

    // Apply sorting by creation date
    $queryBuilder->orderBy('t.creation_date', $sortOrder);

    // Get the results
    $tradeOffers = $queryBuilder->getQuery()->getResult();

    return $this->render('back_trade/back_tradeoffer.html.twig', [
        'trade_offers' => $tradeOffers,
        'search' => $searchTerm, // Pass the search term for receiver to the view
        'sort' => $sortOrder, // Pass the current sort order to the view
        'status' => $status, // Pass the status filter to the view
        'sender_search' => $senderSearchTerm, // Pass the sender search term to the view
    ]);
}


    #[Route('/add', name: 'app_admin_trade_offer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tradeOffer = new TradeOffer();
        $form = $this->createForm(TradeOfferType::class, $tradeOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tradeOffer->setCreationDate(new \DateTime());
            $tradeOffer->setStatus('pending');
            $entityManager->persist($tradeOffer);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_trade', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade/add.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_trade_show', methods: ['GET'])]
    public function show(TradeOffer $tradeOffer): Response
    {
        return $this->render('back_trade/show.html.twig', [
            'trade_offer' => $tradeOffer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_trade_offer_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(TradeOfferType::class, $tradeOffer);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Get the offered and received items
        $offeredItem = $tradeOffer->getOfferedItem();
        $receivedItem = $tradeOffer->getReceivedItem();
        
        // Ensure the admin can swap the items (you can add more checks here)
        // Swap the ownership of the offered and received items
        $a = $offeredItem->getOwner();
        $offeredItem->setOwner($tradeOffer->getReceiverName()); // Set the receiver as the new owner of the offered item
        $receivedItem->setOwner($a); // Set the original owner as the new owner of the received item

        // Persist the changes for the items
        $entityManager->persist($receivedItem);
        $entityManager->persist($offeredItem);

        // Update the trade offer status if needed
        $tradeOffer->setStatus('Updated'); // or any other status based on your logic
        $entityManager->persist($tradeOffer);

        // Flush all changes to the database
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_trade', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('back_trade/edit.html.twig', [
        'trade_offer' => $tradeOffer,
        'form' => $form->createView(),
    ]);
}


    #[Route('/{id}/delete', name: 'app_admin_trade_delete', methods: ['POST'])]
    public function delete(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeOffer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tradeOffer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_trade', [], Response::HTTP_SEE_OTHER);
    }
}
