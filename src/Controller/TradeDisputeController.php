<?php

namespace App\Controller;

use App\Entity\TradeDispute;
use App\Form\TradeDisputeType;
use App\Repository\TradeDisputeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trade/dispute')]
class TradeDisputeController extends AbstractController
{
    #[Route('/', name: 'app_trade_dispute_index', methods: ['GET'])]
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
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute, [
            'is_admin' => false,
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set timestamp and initial status
            $tradeDispute->setTimestamp(new \DateTime());
            $tradeDispute->setStatus('pending');
            
            // Get the selected trade offer and set offered/received items
            $tradeOffer = $tradeDispute->getTradeId();
            if ($tradeOffer) {
                $tradeDispute->setOfferedItem($tradeOffer->getOfferedItem()->getImageName());
                $tradeDispute->setReceivedItem($tradeOffer->getReceivedItem()->getImageName());
            }
            
            /** @var UploadedFile $file */
            $file = $form->get('evidence')->getData();

            if ($file) {
                // Generate a unique file name
                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
                    // Move the file to the directory where evidence is stored
                    $file->move(
                        $this->getParameter('evidence_directory'),
                        $newFilename
                    );
                    $tradeDispute->setEvidence($newFilename);
                } catch (FileException $e) {
                    // Handle the exception if something happens during file upload
                }
            }

            $entityManager->persist($tradeDispute);
            $entityManager->flush();

            return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trade_dispute/new.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form->createView(),
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
        // Store the current evidence filename
        $currentEvidence = $tradeDispute->getEvidence();

        $form = $this->createForm(TradeDisputeType::class, $tradeDispute, [
            'is_admin' => false,
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the selected trade offer and update offered/received items
            $tradeOffer = $tradeDispute->getTradeId();
            if ($tradeOffer) {
                $tradeDispute->setOfferedItem($tradeOffer->getOfferedItem()->getImageName());
                $tradeDispute->setReceivedItem($tradeOffer->getReceivedItem()->getImageName());
            }

            /** @var UploadedFile $file */
            $file = $form->get('evidence')->getData();

            if ($file) {
                // Generate a unique file name
                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
                    // Move the file to the directory where evidence is stored
                    $file->move(
                        $this->getParameter('evidence_directory'),
                        $newFilename
                    );

                    // Delete old evidence file if it exists
                    if ($currentEvidence) {
                        $oldFile = $this->getParameter('evidence_directory') . '/' . $currentEvidence;
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    $tradeDispute->setEvidence($newFilename);
                } catch (FileException $e) {
                    // Handle the exception if something happens during file upload
                }
            } else {
                // If no new file is uploaded, keep the current evidence
                $tradeDispute->setEvidence($currentEvidence);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trade_dispute/edit.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_trade_dispute_delete', methods: ['POST'])]
    public function delete(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        // Delete evidence file if exists
        if ($tradeDispute->getEvidence()) {
            $file = $this->getParameter('evidence_directory').'/'.$tradeDispute->getEvidence();
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        $entityManager->remove($tradeDispute);
        $entityManager->flush();
        
        return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
    }
}
