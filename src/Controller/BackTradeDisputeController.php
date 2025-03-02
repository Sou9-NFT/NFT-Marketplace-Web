<?php

namespace App\Controller;

use App\Entity\TradeDispute;
use App\Form\TradeDisputeType;
use App\Repository\TradeDisputeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back/trade/dispute')]
#[IsGranted('ROLE_ADMIN', message: 'You need admin access for this section')]
class BackTradeDisputeController extends AbstractController
{
    /*#[Route('/', name: 'app_admin_trade_dispute_index', methods: ['GET'])]
    public function index(TradeDisputeRepository $tradeDisputeRepository): Response
    {
        return $this->render('back_trade_dispute/back_dispute.html.twig', [
            'trade_disputes' => $tradeDisputeRepository->findAll(),
        ]);
    }*/
    #[Route('/', name: 'app_admin_trade_dispute_index', methods: ['GET'])]
    public function index(TradeDisputeRepository $tradeDisputeRepository, Request $request): Response
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
    
        return $this->render('back_trade_dispute/back_dispute.html.twig', [
            'trade_disputes' => $disputes,
            'search' => $searchTerm, // Pass search value to template
            'status' => $status, // Pass status filter value to template
            'sort' => $sort, // Pass sort value to template
        ]);
    }

    #[Route('/add', name: 'app_admin_trade_dispute_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tradeDispute = new TradeDispute();
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute, [
            'is_admin' => true,
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the selected TradeOffer
            $tradeOffer = $tradeDispute->getTradeId();

            // Set the offered_item and received_item from the TradeOffer
            $tradeDispute->setOfferedItem($tradeOffer->getOfferedItem()->getImageName());
            $tradeDispute->setReceivedItem($tradeOffer->getReceivedItem()->getImageName());
            
            // Set timestamp and status
            $tradeDispute->setTimestamp(new \DateTime());
            $tradeDispute->setStatus('pending');
            
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

            return $this->redirectToRoute('app_admin_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade_dispute/add.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'app_admin_trade_dispute_show', methods: ['GET'])]
    public function show(TradeDispute $tradeDispute): Response
    {
        return $this->render('back_trade_dispute/show.html.twig', [
            'trade_dispute' => $tradeDispute,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_trade_dispute_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TradeDisputeType::class, $tradeDispute, [
            'is_admin' => true,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_admin_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_trade_dispute/edit.html.twig', [
            'trade_dispute' => $tradeDispute,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_trade_dispute_delete', methods: ['POST'])]
    public function delete(Request $request, TradeDispute $tradeDispute, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tradeDispute->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tradeDispute);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_trade_dispute_index', [], Response::HTTP_SEE_OTHER);
    }
}
