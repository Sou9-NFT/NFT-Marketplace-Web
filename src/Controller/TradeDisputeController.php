<?php

namespace App\Controller;

use App\Entity\TradeDispute;
use App\Form\TradeDisputeType;
use App\Repository\TradeDisputeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trade/dispute')]
final class TradeDisputeController extends AbstractController
{
    #[Route(name: 'app_trade_dispute_index', methods: ['GET'])]
    public function index(TradeDisputeRepository $tradeDisputeRepository): Response
    {
        return $this->render('trade_dispute/index.html.twig', [
            'trade_disputes' => $tradeDisputeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_trade_dispute_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tradeDispute = new TradeDispute();
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tradeDispute);
            $entityManager->flush();

            return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trade_dispute/new.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trade_dispute_show', methods: ['GET'])]
    public function show(TradeDispute $tradeDispute): Response
    {
        return $this->render('trade_dispute/show.html.twig', [
            'trade_dispute' => $tradeDispute,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trade_dispute_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trade_dispute/edit.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trade_dispute_delete', methods: ['POST'])]
    public function delete(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeDispute->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tradeDispute);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
    }
}
