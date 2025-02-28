<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Bid;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BetSession;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\BidRepository;

final class BidController extends AbstractController
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/bid/add', name: 'app_add_bid', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add(Request $request): Response
    {
        $betSessionId = $request->request->get('betSessionId');
        $bidValue = $request->request->get('bidValue');
        $user = $this->getUser();

        try {
            $betSession = $this->entityManager->getRepository(BetSession::class)->find($betSessionId);
            if (!$betSession) {
                $this->addFlash('error_bid', 'Bet session not found');
                return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
            }

            if ($bidValue <= $betSession->getCurrentPrice()) {
                $this->addFlash('error_bid', 'The bid should be greater than the current price');
                return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
            }

            if (!$user instanceof \App\Entity\User) {
                $this->addFlash('error_bid', 'User not found');
                return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
            }

            // Check for existing bid
            $existingBid = $this->entityManager->getRepository(Bid::class)->findOneBy([
                'betSession' => $betSession,
                'author' => $user
            ]);

            if ($existingBid) {
                // Update existing bid
                $oldBidValue = $existingBid->getBidValue();
                $bidDifference = $bidValue - $oldBidValue;

                if ($user->getBalance() < $bidDifference) {
                    $this->addFlash('error_bid', 'Insufficient balance to increase your bid');
                    return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
                }

                $existingBid->setBidValue($bidValue);
                $existingBid->setBidTime(new \DateTime());
                $user->setBalance($user->getBalance() - $bidDifference);
                $betSession->setCurrentPrice($bidValue);
            } else {
                // Create new bid
                if ($user->getBalance() < $bidValue) {
                    $this->addFlash('error_bid', 'Insufficient balance to place this bid');
                    return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
                }

                $bid = new Bid();
                $bid->setAuthor($user);
                $bid->setBetSession($betSession);
                $bid->setBidValue($bidValue);
                $user->setBalance($user->getBalance() - $bidValue);
                $betSession->setCurrentPrice($bidValue);
                $this->entityManager->persist($bid);
            }

            $this->entityManager->persist($betSession);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success_bid', 'Bid updated successfully');
            return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);

        } catch (\Exception $e) {
            $this->logger->error('An error occurred while processing the bid: ' . $e->getMessage());
            $this->addFlash('error_bid', 'An error occurred while processing the bid');
            return $this->redirectToRoute('app_item_details', ['id' => $betSessionId]);
        }
    }

    #[Route('/bet_sessions/{id}/latest', name: 'app_bet_session_latest', methods: ['GET'])]
    public function getLatestData(BetSession $betSession, BidRepository $bidRepository): JsonResponse
    {
        $bids = $bidRepository->findBy(
            ['betSession' => $betSession], 
            ['bidTime' => 'DESC'],
            3
        );
        
        $latestBids = array_reverse(array_map(function ($bid) {
            return [
            'bidValue' => $bid->getBidValue(),
            'bidTime' => $bid->getBidTime()->format('Y-m-d H:i:s'),
            'author' => [
                'name' => $bid->getAuthor()->getName(),
                'profilePicture' => $bid->getAuthor()->getProfilePicture(),
            ],
            ];
        }, $bids));

        return new JsonResponse([
            'currentPrice' => $betSession->getCurrentPrice(),
            'bids' => $latestBids,
        ]);
    }


}
