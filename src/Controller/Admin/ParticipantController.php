<?php

namespace App\Controller\Admin;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/participant')]
class ParticipantController extends AbstractController
{
    private $entityManager;
    private $participantRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParticipantRepository $participantRepository
    ) {
        $this->entityManager = $entityManager;
        $this->participantRepository = $participantRepository;
    }

    #[Route('/', name: 'app_admin_participant_index', methods: ['GET'])]
    public function index(): Response
    {
        $participants = $this->participantRepository->findAll();

        return $this->render('admin/participant/index.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_participant_show', methods: ['GET'])]
    public function show(Participant $participant): Response
    {
        return $this->render('admin/participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_participant_delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participant->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($participant);
            $this->entityManager->flush();
            $this->addFlash('success', 'Participant was successfully deleted.');
        }

        return $this->redirectToRoute('app_admin_participant_index');
    }
}