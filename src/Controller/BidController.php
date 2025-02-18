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

final class BidController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/bid/add', name: 'app_add_bid', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function add(Request $request): Response
    {
        $betSessionId = $request->request->get('betSessionId');
        $bidValue = $request->request->get('bidValue');
        $bid = new Bid();
        $bid->setAuthor($this->getUser());

        if ($bidValue <= 0) {
            $this->addFlash('error_bid', 'The Raise Should be greater than 0');
            return $this->redirectToRoute('app_item_details', [
                'id' => $betSessionId,
            ]);
        }

        try {
            $betSession = $this->entityManager->getRepository(BetSession::class)->find($betSessionId);
            if (!$betSession) {
                $this->addFlash('error_bid', 'Bet session not found');
                return $this->json([
                    'error' => 'Bet session not found',
                ], Response::HTTP_NOT_FOUND);
            }

                
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                $this->addFlash('error_bid', 'User not found');
                return $this->redirectToRoute('app_item_details', [
                    'id' => $betSessionId,
                ]);
            }
            else{
            if ($user->getBalance() < $bidValue) {
                $this->addFlash('error_bid', 'Insufficient balance Go Charge your wallet');
                return $this->redirectToRoute('app_item_details', [
                    'id' => $betSessionId,
                ]);
            }

            

            $bid->setBetSession($betSession);
            $bid->setBidValue($betSession->getCurrentPrice() + $bidValue);
            $betSession->setCurrentPrice($betSession->getCurrentPrice() + $bidValue);
            $user->setBalance($user->getBalance() - $bidValue);

            $this->entityManager->persist($betSession);
            $this->entityManager->persist($bid);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success_bid', 'Bid added successfully');
            return $this->redirectToRoute('app_item_details', [
                'id' => $betSessionId,
            ]);
            }
        } catch (\Exception $e) {
            $this->addFlash('error_bid', 'An error occurred while adding the bid');
        }
    }
}