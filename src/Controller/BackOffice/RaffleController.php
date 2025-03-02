<?php

namespace App\Controller\BackOffice;

use App\Entity\Raffle;
use App\Entity\Participant;
use App\Form\RaffleType;
use App\Repository\RaffleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/raffle')]
class RaffleController extends AbstractController
{
    private $entityManager;
    private $raffleRepository;

    public function __construct(EntityManagerInterface $entityManager, RaffleRepository $raffleRepository)
    {
        $this->entityManager = $entityManager;
        $this->raffleRepository = $raffleRepository;
    }

    private function checkAndUpdateRaffleStatus(Raffle $raffle): void
    {
        $now = new \DateTime();
        
        if ($raffle->getEndTime() <= $now) {
            $raffle->setStatus('ended');
            $this->entityManager->flush();
        } else {
            $raffle->setStatus('active');
            $this->entityManager->flush();
        }
    }

    #[Route('/', name: 'app_admin_raffle_index', methods: ['GET'])]
    public function index(): Response
    {
        $raffles = $this->raffleRepository->findAll();
        
        foreach ($raffles as $raffle) {
            $this->checkAndUpdateRaffleStatus($raffle);
        }

        return $this->render('raffle/raffleback.html.twig', [
            'raffles' => $raffles,
            'debug' => [
                'count' => count($raffles),
                'empty' => empty($raffles),
            ],
        ]);
    }

    #[Route('/new', name: 'app_admin_raffle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $raffle = new Raffle();
        $raffle->setCreatorName($this->getUser()->getName() ?: $this->getUser()->getEmail());
        
        $form = $this->createForm(RaffleType::class, $raffle, [
            'user' => $this->getUser() // Pass the current user to the form
        ]);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $raffle->setStartTime(new \DateTime('now'));
            $raffle->setCreator($this->getUser());
            $raffle->setStatus('active');
            $this->entityManager->persist($raffle);
            $this->entityManager->flush();

            $this->addFlash('success', 'Raffle created successfully.');
            return $this->redirectToRoute('app_admin_raffle_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('raffle/raffle_form_back.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
            'is_edit' => false
        ]);
    }

    #[Route('/{id}', name: 'app_admin_raffle_show', methods: ['GET'])]
    public function show(Raffle $raffle): Response
    {
        $this->checkAndUpdateRaffleStatus($raffle);
        
        $winner = null;
        if ($raffle->getStatus() === 'ended' && count($raffle->getParticipants()) > 0 && !$raffle->getWinnerId()) {
            // Get a random participant as winner
            $participants = $raffle->getParticipants()->toArray();
            $winner = $participants[array_rand($participants)];
            
            // Set winner ID and transfer artwork ownership
            $raffle->setWinnerId($winner->getId());
            $artwork = $raffle->getArtwork();
            $artwork->setOwner($winner->getUser());
            
            $this->entityManager->flush();
        } elseif ($raffle->getWinnerId()) {
            // If winner was already selected, get the winner participant
            $winner = $this->entityManager->getRepository(Participant::class)->find($raffle->getWinnerId());
        }
        
        return $this->render('raffle/show_back.html.twig', [
            'raffle' => $raffle,
            'winner' => $winner,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_raffle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Raffle $raffle): Response
    {
        $form = $this->createForm(RaffleType::class, $raffle, [
            'is_edit' => true,
            'user' => $this->getUser() // Pass the current user to the form
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Raffle updated successfully.');
            return $this->redirectToRoute('app_admin_raffle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('raffle/raffle_form_back.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_raffle_delete', methods: ['POST'])]
    public function delete(Request $request, Raffle $raffle): Response
    {
        if ($this->isCsrfTokenValid('delete'.$raffle->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($raffle);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_raffle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/participants', name: 'app_admin_raffle_participants', methods: ['GET'])]
    public function participants(Raffle $raffle): Response
    {
        return $this->render('raffle/raffleback.html.twig', [
            'raffle' => $raffle,
            'participants' => $raffle->getParticipants(),
        ]);
    }
}
