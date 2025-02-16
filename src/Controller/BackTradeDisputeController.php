<?php

namespace App\Controller;

use App\Entity\TradeDispute;
use App\Form\TradeDisputeType;
use App\Repository\TradeDisputeRepository;
use App\Repository\TradeOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/dispute')]
final class BackTradeDisputeController extends AbstractController
{
    #[Route(name: 'app_trade_dispute_back', methods: ['GET'])]
    public function index(TradeDisputeRepository $tradeDisputeRepository): Response
    {
        return $this->render('back_trade_dispute/back_dispute.html.twig', [
            'trade_disputes' => $tradeDisputeRepository->findAll(),
        ]);
    }

    #[Route('/backAddDispute', name: 'app_trade_dispute_add', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TradeOfferRepository $tradeOfferRepository, ManagerRegistry $doctrine): Response
    {
        $tradeDispute = new TradeDispute();
    $form = $this->createForm(TradeDisputeType::class, $tradeDispute);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Get the selected TradeOffer
        $tradeOffer = $tradeDispute->getTradeId();

        // Set the offered_item and received_item from the TradeOffer
        $tradeDispute->setOfferedItem($tradeOffer->getOfferedItem()->getImageName()); // Assuming getImageName() returns the value
        $tradeDispute->setReceivedItem($tradeOffer->getReceivedItem()->getImageName()); // Assuming getImageName() returns the value
        /** @var UploadedFile $file */
        $file = $form->get('evidence')->getData();
        $tradeDispute->setStatus('pending');
        if ($file) {
            // Generate a unique file name
            $newFilename = uniqid() . '.' . $file->guessExtension();

            try {
                // Move the file to the directory where you want to store it
                $file->move(
                    $this->getParameter('evidence_directory'), // Define this parameter in your services.yaml
                    $newFilename
                );

                // Save the file name in the entity
                $tradeDispute->setEvidence($newFilename);
            } catch (FileException $e) {
                // Handle the error if the file couldn't be uploaded
                // You could add a flash message here for the user
            }
        }
        // Persist the TradeDispute
        $entityManager->persist($tradeDispute);
        $entityManager->flush();

        return $this->redirectToRoute('app_trade_dispute_back', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('back_trade_dispute/back_addDispute.html.twig', [
        'trade_dispute' => $tradeDispute,
        'form' => $form->createView(),
    ]);
    }

    #[Route('/{id}', name: 'app_trade_dispute_backshow', methods: ['GET'])]
    public function show(TradeDispute $tradeDispute): Response
    {
        return $this->render('back_trade_dispute/back_show.html.twig', [
            'trade_dispute' => $tradeDispute,
        ]);
    }

    #[Route('/{id}/backedit', name: 'app_trade_dispute_backedit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_trade_dispute_back', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade_dispute/back_editDispute.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trade_dispute_backdelete', methods: ['POST'])]
    public function delete(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeDispute->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tradeDispute);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trade_dispute_back', [], Response::HTTP_SEE_OTHER);
    }
}
