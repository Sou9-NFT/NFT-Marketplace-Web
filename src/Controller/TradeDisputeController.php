<?php

namespace App\Controller;

use App\Entity\TradeDispute;
use App\Entity\TradeOffer;
use App\Form\TradeDisputeType;
use App\Repository\TradeDisputeRepository;
use App\Repository\TradeOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trade/dispute')]
#[IsGranted('ROLE_USER', message: 'You need to be logged in to access trade disputes')]
class TradeDisputeController extends AbstractController
{
    #[Route('', name: 'app_trade_dispute_index', methods: ['GET'])]
    public function index(Request $request, TradeDisputeRepository $tradeDisputeRepository): Response
    {
        $user = $this->getUser();
        $searchTerm = $request->query->get('search', ''); // Get search input for received item
        $status = $request->query->get('status', ''); // Get the status input for filtering
        $sort = $request->query->get('sort', 'desc'); // Get the sort option (default is descending)
    
        // Get disputes where the user is involved in the trade
        $queryBuilder = $tradeDisputeRepository->createQueryBuilder('d')
            ->leftJoin('d.trade_id', 't')
            ->where('d.reporter = :user')
            ->orWhere('t.sender = :user')
            ->orWhere('t.receiver_name = :user')
            ->setParameter('user', $user)
            ->orderBy('d.timestamp', $sort); // Apply sorting based on timestamp
    
        // Apply search filter for received item if provided
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('d.received_item LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
    
        // Apply status filter if provided
        if (!empty($status)) {
            $queryBuilder->andWhere('d.status = :status')
                ->setParameter('status', $status);
        }
    
        $disputes = $queryBuilder->getQuery()->getResult();
    
        return $this->render('trade_dispute/index.html.twig', [
            'trade_disputes' => $disputes,
            'search' => $searchTerm, // Pass search value to template
            'status' => $status, // Pass status filter value to template
            'sort' => $sort, // Pass sort value to template
        ]);
    }
    
    


    #[Route('/new/{trade_id}', name: 'app_trade_dispute_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, TradeOfferRepository $tradeOfferRepository, int $trade_id): Response
{
    $tradeOffer = $tradeOfferRepository->find($trade_id);

    if (!$tradeOffer) {
        throw $this->createNotFoundException('Trade offer not found.');
    }

    $tradeDispute = new TradeDispute();
    $tradeDispute->setReporter($this->getUser());
    $tradeDispute->setTradeId($tradeOffer);
    $tradeDispute->setOfferedItem($tradeOffer->getOfferedItem()->getTitle());
    $tradeDispute->setReceivedItem($tradeOffer->getReceivedItem()->getTitle());

    $form = $this->createForm(TradeDisputeType::class, $tradeDispute, [
        'is_admin' => false,
        'is_edit' => false
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $tradeDispute->setTimestamp(new \DateTime());
        $tradeDispute->setStatus('pending');

        /** @var UploadedFile $file */
        $file = $form->get('evidence')->getData();

        if ($file) {
            $newFilename = uniqid() . '.' . $file->guessExtension();

            try {
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
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'app_trade_dispute_show', methods: ['GET'])]
    public function show(TradeDispute $tradeDispute): Response
    {
        // Check if user is involved in the dispute
        $user = $this->getUser();
        
        if ($tradeDispute->getReporter() !== $user) {
            throw $this->createAccessDeniedException('You cannot view this dispute.');
        }

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
            /** @var UploadedFile $file */
            $file = $form->get('evidence')->getData();

            if ($file) {
                $newFilename = uniqid() . '.' . $file->guessExtension();

                try {
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
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_trade_dispute_delete', methods: ['POST'])]
    public function delete(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        // Check if user is the reporter
        
        
        if ($this->isCsrfTokenValid('delete'.$tradeDispute->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tradeDispute);
            $entityManager->flush();
        }
        
        return $this->redirectToRoute('app_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
    }
}
