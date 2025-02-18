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

#[Route('/back/trade')]
class BackTradeController extends AbstractController
{
    #[Route(name: 'app_back_trade', methods: ['GET'])]
    public function index(TradeOfferRepository $tradeOfferRepository): Response
    {
        return $this->render('back_trade/back_tradeoffer.html.twig', [
            'trade_offers' => $tradeOfferRepository->findAll(),
        ]);
    }

    #[Route('/add', name: 'app_back_trade_offer_add', methods: ['GET', 'POST'])]
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

            return $this->redirectToRoute('app_back_trade', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade/add.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_back_trade_show', methods: ['GET'])]
    public function show(TradeOffer $tradeOffer): Response
    {
        return $this->render('back_trade/show.html.twig', [
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

        return $this->render('back_trade/edit.html.twig', [
            'trade_offer' => $tradeOffer,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_back_trade_delete', methods: ['POST'])]
    public function delete(Request $request, TradeOffer $tradeOffer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeOffer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tradeOffer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_trade', [], Response::HTTP_SEE_OTHER);
    }
}
