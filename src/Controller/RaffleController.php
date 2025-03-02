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
use App\Service\GeminiService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/raffle')]
class RaffleController extends AbstractController
{
    private $entityManager;
    private $raffleRepository;
    private $recaptchaSecret;

    public function __construct(
        EntityManagerInterface $entityManager, 
        RaffleRepository $raffleRepository,
        string $recaptchaSecret
    ) {
        $this->entityManager = $entityManager;
        $this->raffleRepository = $raffleRepository;
        $this->recaptchaSecret = $recaptchaSecret;
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
        // Check if user is authenticated and redirect to login if not
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
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

    #[Route('/chat', name: 'app_raffle_chat', methods: ['POST'])]
    public function chat(Request $request, GeminiService $geminiService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'Message is required'], 400);
        }

        try {
            $response = $geminiService->generateResponse($userMessage);
            return new JsonResponse(['response' => $response]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
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

    #[Route('/{id}/join', name: 'app_raffle_join', methods: ['POST'])]
    public function join(Request $request, Raffle $raffle): Response
    {
        try {
            /** @var User|null $user */
            $user = $this->getUser();
            if (!$user) {
                throw new \Exception('You must be logged in to join a raffle');
            }

            // Validate CSRF token
            if (!$this->isCsrfTokenValid('join_raffle_' . $raffle->getId(), $request->request->get('_token'))) {
                throw new \Exception('Invalid request');
            }

            // Check and update raffle status first
            $this->checkAndUpdateRaffleStatus($raffle);
            
            // Check if raffle is still active
            if ($raffle->getStatus() !== 'active') {
                throw new \Exception('This raffle is no longer active');
            }
            
            // Verify Google reCAPTCHA
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            if (!$this->verifyRecaptcha($recaptchaResponse, $this->recaptchaSecret)) {
                throw new \Exception('reCAPTCHA verification failed');
            }

            // Check if user has already joined
            $existingParticipant = $this->entityManager->getRepository(Participant::class)
                ->findOneBy(['raffle' => $raffle, 'user' => $user]);
            
            if ($existingParticipant) {
                throw new \Exception('You have already joined this raffle');
            }

            // Create new participant
            $participant = new Participant();
            $participant->setUser($user);
            $participant->setRaffle($raffle);
            $participant->setName($user->getName() ?: explode('@', $user->getEmail())[0]);
            $participant->setJoinedAt(new \DateTime());

            // Persist and flush
            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            $this->addFlash('success', 'You have successfully joined the raffle!');
            
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        
        // Always redirect back to show page
        return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
    }
    
    /**
     * Verify the Google reCAPTCHA response.
     */
    private function verifyRecaptcha(string $recaptchaResponse, string $recaptchaSecret): bool
    {
        if (empty($recaptchaResponse)) {
            return false;
        }

        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($verifyUrl, false, $context);
        $responseData = json_decode($response);

        // Debug: Log the reCAPTCHA verification response
        error_log('reCAPTCHA verification response: ' . print_r($responseData, true));

        return $responseData->success;
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
        // Check if user is the creator
        if ($this->getUser() !== $raffle->getCreator()) {
            return $this->render('error/access_denied.html.twig', [
                'raffle' => $raffle
            ], new Response('', Response::HTTP_FORBIDDEN));
        }

        if ($this->isCsrfTokenValid('delete'.$raffle->getId(), $request->request->get('_token'))) {
            try {
                // Delete image file if exists
                if ($raffle->getImage()) {
                    $imagePath = $this->getParameter('raffle_images_directory').'/'.$raffle->getImage();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                $this->entityManager->remove($raffle);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Raffle was successfully deleted.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting raffle: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid token.');
        }

        return $this->redirectToRoute('app_raffle_index');
    }
}