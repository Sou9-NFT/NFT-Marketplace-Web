<?php

namespace App\Controller;

use App\Entity\TopUpRequest;
use App\Repository\TopUpRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/topup-requests')]
#[IsGranted('ROLE_ADMIN')]
class AdminTopUpController extends AbstractController
{
    #[Route('/', name: 'app_admin_topup_index')]
    public function index(TopUpRequestRepository $topUpRequestRepository): Response
    {
        return $this->render('back_user/topup_requests.html.twig', [
            'topup_requests' => $topUpRequestRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_topup_approve', methods: ['POST'])]
    public function approve(TopUpRequest $topUpRequest, EntityManagerInterface $entityManager): Response
    {
        $topUpRequest->setStatus('approved');
        $topUpRequest->setProcessedAt(new \DateTimeImmutable());
        
        // Update user's balance
        $user = $topUpRequest->getUser();
        $user->setBalance($user->getBalance() + $topUpRequest->getAmount());
        
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/reject', name: 'app_admin_topup_reject', methods: ['POST'])]
    public function reject(TopUpRequest $topUpRequest, EntityManagerInterface $entityManager): Response
    {
        $topUpRequest->setStatus('rejected');
        $topUpRequest->setProcessedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_topup_index');
    }
}