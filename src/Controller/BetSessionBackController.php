<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BetSessionRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\BetSession;
use App\Form\BetSessionType;
use App\Entity\User;
#[Route('/admin/auctions')]
final class BetSessionBackController extends AbstractController
{
    #[Route("/All",name: 'app_bet_session_index', methods: ['GET'])]
    public function index(BetSessionRepository $betSessionRepository): Response
    {
        return $this->render('bet_session_back/allBetSessions.html.twig', [
            'bet_sessions' => $betSessionRepository->findAll(),
        ]);
    }
    
    #[Route('/create', name: 'app_bet_session_create', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $betSession = new BetSession();
        $form = $this->createForm(BetSessionType::class, $betSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
                $entityManager->persist($user);
                $betSession->setAuthor($user);
                $betSession->setCurrentPrice($betSession->getInitialPrice());
                $entityManager->persist($betSession);
                $entityManager->flush();
                $this->addFlash('success', 'Bet session created successfully.');
                return $this->redirectToRoute('app_bet_session_index', [], Response::HTTP_SEE_OTHER);
  
        }
     
        return $this->render('bet_session_back/createBetSession.html.twig', [
            'bet_session' => $betSession,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_bet_session_show', methods: ['GET'])]
    public function show(BetSession $betSession): Response
    {
        return $this->render('bet_session_back/showBetSession.html.twig', [
            'bet_session' => $betSession,
        ]);
    }

    #[Route('/{id}', name: 'app_bet_session_delete_admin', methods: ['POST'])]
    public function delete(Request $request, BetSession $betSession, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$betSession->getId(), $request->request->get('_token'))) {
             /** @var User $user */
            $user = $this->getUser();
            $entityManager->remove($betSession);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_bet_session_index', [], Response::HTTP_SEE_OTHER);
    }

}
