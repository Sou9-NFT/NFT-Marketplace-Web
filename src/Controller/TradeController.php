<?php

namespace App\Controller;

use App\Entity\TradeOffer;
use App\Entity\User;
use App\Entity\TradeState;
use App\Entity\Artwork;
use App\Form\TradeOfferType;
use App\Repository\TradeOfferRepository;
use App\Repository\ArtworkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
//use Symfony\Component\Mailer\MailerInterface;
//use Symfony\Component\Mime\Email;


#[Route('/trade')]
#[IsGranted('ROLE_USER', message: 'You need to be logged in to access this section')]
class TradeController extends AbstractController
{
    #[Route('/offer', name: 'app_trade_offer_index', methods: ['GET'])]
public function index(Request $request, TradeOfferRepository $tradeOfferRepository): Response
{
    $user = $this->getUser();
    $searchTerm = $request->query->get('search', ''); // Get the search term from the query string
    $sortOrder = $request->query->get('sort', 'asc'); // Get the sort order from the query string
    $status = $request->query->get('status', ''); // Get the status filter

    // Start building the query
    $queryBuilder = $tradeOfferRepository->createQueryBuilder('t')
        ->leftJoin('t.receiver_name', 'r') // Join User entity via receiver_name relation
        ->where('t.sender = :user')
        ->orWhere('t.receiver_name = :user')
        ->setParameter('user', $user)
        ->andWhere('r.name LIKE :search') // Filter by receiver's name
        ->setParameter('search', '%' . $searchTerm . '%') // Use the search term
        ->orderBy('t.creation_date', $sortOrder); // Sorting by creation date

    // Add the status filter if it's provided
    if ($status) {
        $queryBuilder->andWhere('t.status = :status') // Filter by status
                     ->setParameter('status', $status);
    }

    // Get the results
    $tradeOffers = $queryBuilder->getQuery()->getResult();

    return $this->render('trade/showtradeoffer.html.twig', [
        'trade_offers' => $tradeOffers,
        'search' => $searchTerm, // Pass the search term back to the view
        'sort' => $sortOrder, // Pass the current sort order to the view
        'status' => $status, // Pass the status filter to the view
    ]);
}



#[Route('/offer/new/{artwork_id}', name: 'app_trade_offer_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, int $artwork_id, ArtworkRepository $artworkRepository): Response
{
    // Fetch the artwork manually
    $artwork = $entityManager->getRepository(Artwork::class)->find($artwork_id);

    if (!$artwork) {
        throw $this->createNotFoundException('Artwork not found');
    }

    // Check if the current user is not the owner of the artwork
    if ($artwork->getOwner() === $this->getUser()) {
        $this->addFlash('error', 'You cannot trade with your own artwork.');
        return $this->redirectToRoute('app_artwork_index');
    }

    // Create a new trade offer with pre-filled data
    $tradeOffer = new TradeOffer();
    $tradeOffer->setReceiverName($artwork->getOwner());
    $tradeOffer->setReceivedItem($artwork);
    $tradeOffer->setSender($this->getUser());
    $tradeOffer->setStatus('pending');
    $tradeOffer->setCreationDate(new \DateTime());

    $form = $this->createForm(TradeOfferType::class, $tradeOffer, [
        'receiver_readonly' => true,
        'received_item_readonly' => true,
        'receiver_artwork' => $artwork
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Fetch the offered item from the form
        $offeredItem = $tradeOffer->getOfferedItem();

        // Ensure the offered item belongs to the current user
        if ($offeredItem->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'You can only offer artworks that you own.');
            return $this->redirectToRoute('app_trade_offer_new', ['artwork_id' => $artwork_id]);
        }

        // Persist the trade offer
        $entityManager->persist($tradeOffer);
        $entityManager->flush();

        // Create a new TradeState entity
        $tradeState = new TradeState();
        $tradeState->setTradeOffer($tradeOffer);
        $tradeState->setReceivedItem($tradeOffer->getReceivedItem());
        $tradeState->setOfferedItem($tradeOffer->getOfferedItem());
        $tradeState->setSender($tradeOffer->getSender());
        $tradeState->setReceiver($tradeOffer->getReceiverName());
        $tradeState->setDescription($tradeOffer->getDescription());

        // Persist the trade state
        $entityManager->persist($tradeState);
        $entityManager->flush();

        $this->addFlash('success', 'Trade offer created successfully, and TradeState has been set.');
        return $this->redirectToRoute('app_trade_offer_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('trade/addtradeoffer.html.twig', [
        'trade_offer' => $tradeOffer,
        'form' => $form,
    ]);
}


    #[Route('/offer/{id}', name: 'app_trade_offer_show', methods: ['GET'])]
    public function show(TradeOffer $tradeOffer): Response
    {
        $user = $this->getUser();
        if ($tradeOffer->getSender() !== $user && $tradeOffer->getReceiverName() !== $user) {
            throw $this->createAccessDeniedException('You cannot view this trade offer.');
        }

        return $this->render('trade/viewtradeoffer.html.twig', [
            'trade_offer' => $tradeOffer,
        ]);
    }

    #[Route('/offer/{id}/edit', name: 'app_trade_offer_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
{
    // Check if user is the sender of the trade
    if ($tradeOffer->getSender() !== $this->getUser()) {
        throw $this->createAccessDeniedException('You can only edit your own trade offers.');
    }

    $form = $this->createForm(TradeOfferType::class, $tradeOffer, [
        'receiver_readonly' => true,
        'received_item_readonly' => true
    ]);
    $form->handleRequest($request);

    // Fetch the related TradeState entity
    $tradeState = $entityManager->getRepository(TradeState::class)->findOneBy(['tradeOffer' => $tradeOffer]);

    if (!$tradeState) {
        $this->addFlash('error', 'Trade state not found for this offer.');
        return $this->redirectToRoute('app_trade_offer_index');
    }

    if ($form->isSubmitted() && $form->isValid()) {
        // Fetch the offered item and description from the form
        $newOfferedItem = $tradeOffer->getOfferedItem();
        $newDescription = $tradeOffer->getDescription();

        // Update the offered item and description in the TradeState
        $tradeState->setOfferedItem($newOfferedItem);
        $tradeState->setDescription($newDescription);

        // Persist the updated TradeState
        $entityManager->persist($tradeState);
        $entityManager->flush();

        $this->addFlash('success', 'Trade offer and trade state updated successfully.');
        return $this->redirectToRoute('app_trade_offer_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('trade/edit.html.twig', [
        'trade_offer' => $tradeOffer,
        'form' => $form->createView(),
    ]);
}



    #[Route('/offer/{id}/delete', name: 'app_trade_offer_delete', methods: ['POST'])]
    public function delete(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        // Check if user is the sender of the trade
        if ($tradeOffer->getSender() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own trade offers.');
        }

        if ($this->isCsrfTokenValid('delete'.$tradeOffer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tradeOffer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trade_offer_index', [], Response::HTTP_SEE_OTHER);
    }

    



}