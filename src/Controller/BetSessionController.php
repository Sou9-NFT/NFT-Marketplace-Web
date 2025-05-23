<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Entity\BetSession;
use App\Entity\User;
use App\Form\BetSessionType;
use App\Repository\BetSessionRepository;
use App\Repository\BidRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Service\GeminiNftDescriptionService;

#[Route('/auctions')]
final class BetSessionController extends AbstractController
{
    private $geminiService;
    
    public function __construct(GeminiNftDescriptionService $geminiService) 
    {
        $this->geminiService = $geminiService;
    }

    
    #[Route('/', name: 'app_bet_session_active', methods: ['GET'])]
    public function active(BetSessionRepository $betSessionRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 4;
    
        $query = $betSessionRepository->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', 'active')
            ->getQuery();
    
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);
    
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);
    
        return $this->render('bet_session/index.html.twig', [
            'bet_sessions' => $paginator,
            'total_items' => $totalItems,
            'pages_count' => $pagesCount,
            'current_page' => $page,
        ]);
    }

    #[Route('/List/{userId}', name: 'app_bet_session_mylist', methods: ['GET'])]
    public function mylist(int $userId, BetSessionRepository $betSessionRepository, Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($user->getId() !== $userId) {
            return $this->render('error/index.html.twig');
        }

        $page = $request->query->getInt('page', 1);
        $limit = 4;

        $query = $betSessionRepository->createQueryBuilder('b')
            ->where('b.author = :user')
            ->andWhere('b.status != :status')
            ->setParameter('user', $userId)
            ->setParameter('status', 'withdrawn')
            ->getQuery();

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $this->render('bet_session/list.html.twig', [
            'bet_sessions' => $paginator,
            'total_items' => $totalItems,
            'pages_count' => $pagesCount,
            'current_page' => $page,
        ]);
    }




    #[Route('/new', name: 'app_bet_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        $betSession = new BetSession();
        $form = $this->createForm(BetSessionType::class, $betSession, [
            'user' => $this->getUser(),
        ]);
        $form->handleRequest($request);
        $existingBetSession = $entityManager->getRepository(BetSession::class)
            ->createQueryBuilder('b')
            ->where('b.artwork = :artwork')
            ->andWhere('b.status IN (:statuses)')
            ->setParameter('artwork', $betSession->getArtwork())
            ->setParameter('statuses', ['active', 'pending'])
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingBetSession) {
            $this->addFlash('error_new_betsession', 'This artwork is already in an active or pending auction.');
            return $this->redirectToRoute('app_bet_session_new');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $betSession->setAuthor($user);
            $betSession->setCurrentPrice($betSession->getInitialPrice());
            $artwork = $betSession->getArtwork();

            if ($betSession->isMysteriousMode()) {
                $generatedDescription = $this->geminiService->generateNftDescription($artwork);
                if ($generatedDescription) {
                    $betSession->setGeneratedDescription($generatedDescription);
                }
            }
            $entityManager->persist($betSession);
            $entityManager->flush();
            return $this->redirectToRoute('app_bet_session_mylist', ['userId' => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bet_session/new.html.twig', [
            'bet_session' => $betSession,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/withdraw', name: 'app_bet_session_withdraw', methods: ['POST'])]
    public function withdraw(Request $request, BetSession $betSession, EntityManagerInterface $entityManager): Response
    {
        try {
            if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
                /** @var User $user */
                $user = $this->getUser();
                if ($betSession->getAuthor()->getId() === $user->getId() && $betSession->getStatus() === 'pending') {
                    $betSession->setStatus('withdrawn');
                    $entityManager->flush();
                    $this->addFlash('success', 'Bet session withdrawn successfully.');
                    
                    return $this->redirectToRoute('app_bet_session_mylist', ['userId' => $user->getId()], Response::HTTP_SEE_OTHER);
                } else {
                    throw $this->createAccessDeniedException('You are not authorized to withdraw this bet session.');
                }
            } else {
                $this->addFlash('error', 'You need to be authenticated to withdraw a bet session.');
                return $this->redirectToRoute('app_login');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while withdrawing the bet session: ' . $e->getMessage());
            error_log('An error occurred while withdrawing the bet session: ' . $e->getMessage());
            return $this->redirectToRoute('app_bet_session_active');
        }
    }


    #[Route('/{id}/edit', name: 'app_bet_session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BetSession $betSession, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(BetSessionType::class, $betSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
             /** @var User $user */
            $user = $this->getUser();   
            return $this->redirectToRoute('app_bet_session_mylist', ['userId' =>  $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bet_session/edit.html.twig', [
            'bet_session' => $betSession,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_bet_session_delete', methods: ['POST'])]
    public function delete(Request $request, BetSession $betSession, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$betSession->getId(), $request->request->get('_token'))) {
             /** @var User $user */
            $user = $this->getUser();
            $entityManager->remove($betSession);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bet_session_mylist', ['userId' => $user->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ItemDetails/{id}', name: 'app_item_details', methods: ['GET'])]
    public function ItemDetails(int $id, BetSessionRepository $betSessionRepository , BidRepository $bidRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }
        $betSession = $betSessionRepository->find($id);

        if (!$betSession) {
            throw $this->createNotFoundException('The bet session does not exist');
        }

        $bids = $bidRepository->createQueryBuilder('b')
        ->where('b.betSession = :betSession')
        ->setParameter('betSession', $betSession)
        ->orderBy('b.bidTime', 'DESC')
        ->setMaxResults(3)
        ->getQuery()
        ->getResult();


         if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            /** @var User $user */
            $user = $this->getUser();
            if ($betSession->getAuthor()->getId() === $user->getId()) {
                return $this->render('bet_session/MyItemsDetail.html.twig', [
                    'bet_session' => $betSession,
                    'bids' => $bids,
                ]);
            }
        }
        if ($betSession->getStatus() !== 'active') {
            return $this->render('error/index.html.twig');
        }

        $activeBetSessions = $betSessionRepository->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', 'active')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

     
     
        return $this->render('bet_session/ItemDetails.html.twig', [
            'bet_session' => $betSession,
            'live_bet_sessions' => $activeBetSessions,
            'bids' => $bids,
        ]);
    }

    #[Route('/{id}/end', name: 'app_bet_session_end', methods: ['GET', 'PUT'])]
    public function end(BetSession $betSession, EntityManagerInterface $entityManager, BidRepository $bidRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('app_login');
        }

        try {
            if ($betSession->getEndTime() > new \DateTime()) {
                throw new \Exception('Auction has not ended yet.');
            }

            // Get all bids for this session ordered by amount DESC
            $bids = $bidRepository->findBy(['betSession' => $betSession], ['bidValue' => 'DESC']);
            
            // Get winning bid (highest amount)
            $winningBid = !empty($bids) ? $bids[0] : null;

            // Process refunds for non-winning bids
            foreach ($bids as $bid) {
                if ($bid !== $winningBid) {
                    $bidder = $bid->getAuthor();
                    $bidder->setBalance($bidder->getBalance() + $bid->getBidValue());
                }
            }


            if ($winningBid) {
                // Transfer artwork ownership to winner
                $artwork = $betSession->getArtwork();
                $winner = $winningBid->getAuthor();
                $artwork->setOwner($winner);
                $author = $betSession->getAuthor();
                $author->setBalance($author->getBalance() + $betSession->getCurrentPrice());

                // Create and send notification to winner
                $notification = new \App\Entity\Notification();
                $notification->setReceiver($winner);
                $notification->setTitle("Auction Won! You've won the auction for '" . $artwork->getTitle() . "'");
                $notification->setType("auction_win");
                $notification->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($notification);
                
                // Update bet session status
                $betSession->setStatus('ended');
            } else {
                $betSession->setStatus('ended');
            }
            
            $entityManager->flush();
            return $this->redirectToRoute('app_home_page');

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


}