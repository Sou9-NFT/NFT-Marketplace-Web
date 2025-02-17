<?php
// src/Controller/TradeController.php
namespace App\Controller;

use App\Entity\TradeOffer;
use App\Entity\User;
use App\Form\TradeOfferType;
use App\Repository\TradeOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trade/offer')]
class TradeController extends AbstractController
{
    #[Route(name: 'app_trade_offer_index', methods: ['GET'])]
    public function index(TradeOfferRepository $tradeOfferRepository): Response
    {
        return $this->render('trade/showtradeoffer.html.twig', [
            'trade_offers' => $tradeOfferRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'app_trade_offer_add', methods: ['GET', 'POST'])]
    public function add(Request $request, ManagerRegistry $doctrine): Response
    {
        $tradeOffer = new TradeOffer();
        $form = $this->createForm(TradeOfferType::class, $tradeOffer);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $tradeOffer->setCreationDate(new \DateTime());
            $em = $doctrine->getManager();
            $em->persist($tradeOffer);
            $em->flush();

            return $this->redirectToRoute('app_trade_show', ['id' => $tradeOffer->getId()]);
        }

        return $this->render('trade/addtradeoffer.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_trade_show', methods: ['GET'])]
    public function show(TradeOffer $tradeOffer): Response
    {
        return $this->render('trade/viewtradeoffer.html.twig', [
            'trade_offer' => $tradeOffer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trade_offer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TradeOfferType::class, $tradeOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_trade_offer_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trade/edit.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_trade_offer_delete', methods: ['POST'])]
    public function delete(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($tradeOffer);
        $entityManager->flush();
        
        return $this->redirectToRoute('app_trade_offer_index', [], Response::HTTP_SEE_OTHER);
    }
}
