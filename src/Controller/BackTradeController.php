<?php

namespace App\Controller;
use App\Entity\TradeOffer;
use App\Form\TradeOfferType;
use App\Repository\ArtworkRepository;
use App\Repository\TradeOfferRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/trade')]
final class BackTradeController extends AbstractController
{
    #[Route(name: 'app_back_trade', methods: ['GET'])]
    public function index(TradeOfferRepository $tradeOfferRepository): Response
    {
        return $this->render('back_trade/back_tradeoffer.html.twig', [
            'trade_offers' => $tradeOfferRepository->findAll(),
        ]);
    }

    
    #[Route('/add', name: 'app_back_trade_offer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, TradeOfferRepository $tradeOfferRepository, UserRepository $userRepository, ArtworkRepository $artworkRepository, ManagerRegistry $doctrine): Response
    {
        $tradeOffer = new TradeOffer();
        $users = $userRepository->findAll();
        $usersList = [];
        foreach ($users as $user) {
            $usersList[$user->getName()] = $user->getId(); // Assuming you want the username to be the display value
        }

        // Get the list of artworks for offered and received items
        $artworks = $artworkRepository->findAll();
        $artworksList = [];
        foreach ($artworks as $artwork) {
            $artworksList[$artwork->getImageName()] = $artwork->getId(); // Assuming artwork has a name and id
        }

        // Create the form and pass the data
        $form = $this->createForm(TradeOfferType ::class, $tradeOffer, [
            'users' => $usersList,   // Users for receiver_name
            'artworks' => $artworksList  // Artworks for offered_item and received_item
        ]);

        $form->handleRequest($request);
        $tradeOffer->setCreationDate(new \DateTime());
        //echo($form->isSubmitted());
        //echo($form->isValid());
        if ($form->isSubmitted() ) {
            // Manually persist the trade offer using the entity manager
            
            $em = $doctrine->getManager();
            $em->persist($tradeOffer);
            $em->flush();  // Force the database insert

            // Redirect after saving the data
            return $this->redirectToRoute('app_back_trade');  // Redirect after saving
        }

        return $this->render('back_trade/back_addtradeoffer.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
            'users' => $usersList,  // Pass the users data to the template
            'artworks' => $artworksList  // Pass the artworks data to the template
        ]);
    }

    #[Route('/{id}', name: 'app_back_trade_show', methods: ['GET'])]
    public function show(TradeOffer $tradeOffer): Response
    {
        return $this->render('back_trade/back_viewtradeoffer.html.twig', [
            'trade_offer' => $tradeOffer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_trade_offer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TradeOfferType::class, $tradeOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_trade', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade/back_edit.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_trade_offer_delete', methods: ['POST'])]
    public function delete(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeOffer->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tradeOffer);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_back_trade', [], Response::HTTP_SEE_OTHER);
    }

}
