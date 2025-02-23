<?php

namespace App\Controller;

use App\Entity\Raffle;
use App\Entity\User;
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

#[Route('/raffle')]
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
        
        // If end time has passed, mark it as ended
        if ($raffle->getEndTime() <= $now) {
            $raffle->setStatus('ended');
            $this->entityManager->flush();
        }
        // Otherwise, it's active
        else {
            $raffle->setStatus('active');
            $this->entityManager->flush();
        }
    }

    #[Route('/', name: 'app_raffle_index', methods: ['GET'])]
    public function index(): Response
    {
        $raffles = $this->raffleRepository->findAll();

        // Check and update status for all raffles
        foreach ($raffles as $raffle) {
            $this->checkAndUpdateRaffleStatus($raffle);
        }

        return $this->render('raffle/index.html.twig', [
            'raffles' => $raffles,
        ]);
    }

    #[Route('/new', name: 'app_raffle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $raffle = new Raffle();
        $form = $this->createForm(RaffleType::class, $raffle);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Handle image upload
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
    
                    try {
                        $imageFile->move(
                            $this->getParameter('raffle_images_directory'),
                            $newFilename
                        );
                        $raffle->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                        return $this->redirectToRoute('app_raffle_new');
                    }
                }
    
                // Set the start time to now
                $raffle->setStartTime(new \DateTime('now'));
                
                $raffle->setCreator($this->getUser());
                $raffle->setStatus('active');
                $this->entityManager->persist($raffle);
                $this->entityManager->flush();
    
                return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()], Response::HTTP_SEE_OTHER);
            } else {
                // Form is not valid, add flash message or log errors
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }
    
        return $this->render('raffle/new.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_raffle_show', methods: ['GET'])]
    public function show(Raffle $raffle): Response
    {
        // Check and update raffle status
        $this->checkAndUpdateRaffleStatus($raffle);

        // If raffle has ended, select a winner if not already selected
        $winner = null;
        if ($raffle->getStatus() === 'ended' && count($raffle->getParticipants()) > 0) {
            // Get a random participant as winner
            $participants = $raffle->getParticipants()->toArray();
            $winner = $participants[array_rand($participants)];
        }

        return $this->render('raffle/show.html.twig', [
            'raffle' => $raffle,
            'winner' => $winner,
        ]);
    }

    #[Route('/{id}/join', name: 'app_raffle_join', methods: ['GET'])]
    public function join(Request $request, Raffle $raffle): Response
    {
        // Check and update raffle status first
        $this->checkAndUpdateRaffleStatus($raffle);

        // Check if user is authenticated
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'You must be logged in to join a raffle');
            return $this->redirectToRoute('app_login');
        }

        // Check if raffle is still active
        if ($raffle->getStatus() !== 'active') {
            $this->addFlash('error', 'This raffle is no longer active.');
            return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
        }

        // Check if user has already joined
        foreach ($raffle->getParticipants() as $existingParticipant) {
            if ($existingParticipant->getUser() === $user) {
                $this->addFlash('error', 'You have already joined this raffle.');
                return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
            }
        }

        // Automatically create participant with user's name
        $participant = new Participant();
        $participant->setRaffle($raffle);
        $participant->setUser($user);
        // Get user's name or fallback to email username
        $name = $user->getName();
        if (!$name) {
            $name = explode('@', $user->getEmail())[0];
        }
        $participant->setName($name);
        $participant->setJoinedAt(new \DateTime());
        
        $this->entityManager->persist($participant);
        $this->entityManager->flush();

        $this->addFlash('success', 'You have successfully joined the raffle!');
        return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_raffle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Raffle $raffle, SluggerInterface $slugger): Response
    {
        // Check if user is the creator
        if ($this->getUser() !== $raffle->getCreator()) {
            return $this->render('error/access_denied.html.twig', [
                'raffle' => $raffle
            ], new Response('', Response::HTTP_FORBIDDEN));
        }

        // Store the original start time
        $originalStartTime = $raffle->getStartTime();
        
        $form = $this->createForm(RaffleType::class, $raffle, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('raffle_images_directory'),
                        $newFilename
                    );
                    $raffle->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error uploading image: ' . $e->getMessage());
                    return $this->redirectToRoute('app_raffle_edit', ['id' => $raffle->getId()]);
                }
            }

            // Keep the original start time
            $raffle->setStartTime($originalStartTime);
            
            $this->entityManager->flush();
            $this->addFlash('success', 'Raffle updated successfully.');
            return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
        }

        return $this->render('raffle/edit.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/participants', name: 'app_raffle_participants', methods: ['GET'])]
    public function participants(Raffle $raffle): Response
    {
        // Check and update raffle status
        $this->checkAndUpdateRaffleStatus($raffle);

        return $this->render('raffle/participants.html.twig', [
            'raffle' => $raffle
        ]);
    }

    #[Route('/{id}', name: 'app_raffle_delete', methods: ['POST'])]
    public function delete(Request $request, Raffle $raffle): Response
    {
        if ($this->isCsrfTokenValid('delete'.$raffle->getId(), $request->request->get('_token'))) {
            // Delete image file if exists
            if ($raffle->getImage()) {
                $imagePath = $this->getParameter('raffle_images_directory').'/'.$raffle->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $this->entityManager->remove($raffle);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_raffle_index');
    }

}
