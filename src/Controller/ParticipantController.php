<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Raffle;
use App\Entity\User;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/participant')]
class ParticipantController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_participant_index', methods: ['GET'])]
    public function index(): Response
    {
        $participants = $this->entityManager
            ->getRepository(Participant::class)
            ->findAll();

        return $this->render('participant/index.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/admin', name: 'app_participant_admin', methods: ['GET'])]
    public function adminIndex(): Response
    {
        $participants = $this->entityManager
            ->getRepository(Participant::class)
            ->findAll();

        return $this->render('participant/participantsback.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/new', name: 'app_participant_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $participant->setJoinedAt(new \DateTime());
            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_participant_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participant/new.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participant_show', methods: ['GET'])]
    public function show(Participant $participant): Response
    {
        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_participant_show_admin', methods: ['GET'])]
    public function showAdmin(Participant $participant): Response
    {
        return $this->render('participant/showback.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_participant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participant $participant): Response
    {
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            return $this->redirectToRoute('app_participant_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_participant_delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participant->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($participant);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_participant_admin', [], Response::HTTP_SEE_OTHER);
    }
}
