<?php

namespace App\Controller;

use App\Entity\TopUpRequest;
use App\Form\TopUpRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserTopUpController extends AbstractController
{
    #[Route('/user/topup/request', name: 'app_user_topup_request')]
    #[IsGranted('ROLE_USER')]
    public function requestTopUp(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user->getWalletAddress()) {
            $this->addFlash('error', 'Please connect your wallet first.');
            return $this->redirectToRoute('app_wallet_connect');
        }

        $topUpRequest = new TopUpRequest();
        $form = $this->createForm(TopUpRequestType::class, $topUpRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $topUpRequest->setUser($user);
            $entityManager->persist($topUpRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Your top-up request has been submitted successfully.');
            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/topup_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}