<?php

namespace App\Controller;

use App\Entity\Raffle;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/raffle')]
class RaffleController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    private function checkAndUpdateRaffleStatus(Raffle $raffle): void
    {
        if ($raffle->getStatus() === 'active' && $raffle->getEndTime() <= new \DateTime()) {
            $raffle->setStatus('ended');
            
            // Select a random winner when the raffle ends
            $participants = $raffle->getParticipants();
            if (count($participants) > 0) {
                $winner = $participants[array_rand($participants->toArray())];
                $raffle->setWinnerId($winner->getId());
            }
            
            $this->entityManager->flush();
        }
    }

    #[Route('/', name: 'app_raffle_index', methods: ['GET'])]
    public function index(): Response
    {
        $raffles = $this->entityManager
            ->getRepository(Raffle::class)
            ->findAll();

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
        $old = [
            'creator_name' => '',
            'start_time' => '',
            'end_time' => '',
            'image_name' => ''
        ];

        if ($request->isMethod('POST')) {
            $errors = [
                'creator_name' => null,
                'image' => null,
                'start_time' => null,
                'end_time' => null
            ];
            $hasErrors = false;
            
            // Keep old values
            $old['creator_name'] = $request->request->get('creator_name');
            $old['start_time'] = $request->request->get('start_time');
            $old['end_time'] = $request->request->get('end_time');
            
            // Validate creator name
            if (empty($old['creator_name'])) {
                $errors['creator_name'] = 'Creator name is required';
                $hasErrors = true;
            } elseif (strlen($old['creator_name']) < 2 || strlen($old['creator_name']) > 50) {
                $errors['creator_name'] = 'Creator name must be between 2 and 50 characters';
                $hasErrors = true;
            }
            
            // Validate start and end times
            $startTime = null;
            $endTime = null;
            $now = new \DateTime();
            
            // Check if dates are provided
            if (empty($old['start_time'])) {
                $errors['start_time'] = 'Start time is required';
                $hasErrors = true;
            }
            
            if (empty($old['end_time'])) {
                $errors['end_time'] = 'End time is required';
                $hasErrors = true;
            }

            if (!empty($old['start_time']) && !empty($old['end_time'])) {
                try {
                    $startTime = new \DateTime($old['start_time']);
                    $endTime = new \DateTime($old['end_time']);
                    
                    // Validate start time is in the future
                    if ($startTime <= $now) {
                        $errors['start_time'] = 'Start time must be in the future';
                        $hasErrors = true;
                    }
                    
                    // Calculate time difference in minutes
                    $timeDiff = $startTime->diff($endTime);
                    $minutesDiff = ($timeDiff->days * 24 * 60) + ($timeDiff->h * 60) + $timeDiff->i;
                    
                    // Validate end time is at least 1 minute after start time
                    if ($startTime >= $endTime || $minutesDiff < 1) {
                        $errors['start_time'] = 'Start time must be at least 1 minute before end time';
                        $errors['end_time'] = 'End time must be at least 1 minute after start time';
                        $hasErrors = true;
                    }
                } catch (\Exception $e) {
                    $errors['start_time'] = 'Invalid date format';
                    $errors['end_time'] = 'Invalid date format';
                    $hasErrors = true;
                }
            }
            
            // Handle file upload
            $imageFile = $request->files->get('image');
            if (!$imageFile) {
                $errors['image'] = 'Please upload an image';
                $hasErrors = true;
            } else {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $old['image_name'] = $imageFile->getClientOriginalName();

                try {
                    $imageFile->move(
                        $this->getParameter('raffle_images_directory'),
                        $newFilename
                    );
                } catch (\Exception $e) {
                    $errors['image'] = 'Error uploading image';
                    $hasErrors = true;
                }
            }

            if (!$hasErrors) {
                $raffle = new Raffle();
                $raffle->setStartTime($startTime);
                $raffle->setEndTime($endTime);
                $raffle->setCreatedBy(1);
                $raffle->setCreatorName($old['creator_name']);
                $raffle->setImage($newFilename);
                
                $this->entityManager->persist($raffle);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_raffle_index');
            }

            // If there are errors, render the form again with error messages and old values
            return $this->render('raffle/new.html.twig', [
                'form_errors' => $errors,
                'old' => $old
            ]);
        }

        return $this->render('raffle/new.html.twig', [
            'form_errors' => [
                'creator_name' => null,
                'image' => null,
                'start_time' => null,
                'end_time' => null
            ],
            'old' => $old
        ]);
    }

    #[Route('/{id}', name: 'app_raffle_show', methods: ['GET'])]
    public function show(Raffle $raffle): Response
    {
        // Check and update raffle status
        $this->checkAndUpdateRaffleStatus($raffle);

        // Get winner name if exists
        $winner_name = null;
        if ($raffle->getWinnerId()) {
            foreach ($raffle->getParticipants() as $participant) {
                if ($participant->getId() === $raffle->getWinnerId()) {
                    $winner_name = $participant->getName();
                    break;
                }
            }
        }

        return $this->render('raffle/show.html.twig', [
            'raffle' => $raffle,
            'winner_name' => $winner_name,
        ]);
    }

    #[Route('/{id}/join', name: 'app_raffle_join', methods: ['GET', 'POST'])]
    public function join(Request $request, Raffle $raffle): Response
    {
        // Check if raffle is still active
        $this->checkAndUpdateRaffleStatus($raffle);
        
        if ($raffle->getStatus() !== 'active') {
            $this->addFlash('error', 'This raffle is no longer active.');
            return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
        }

        if ($request->isMethod('POST')) {
            $errors = ['name' => null];
            $hasErrors = false;
            
            $name = $request->request->get('name');
            
            // Validate name
            if (empty($name)) {
                $errors['name'] = 'Name is required';
                $hasErrors = true;
            } elseif (strlen($name) < 2 || strlen($name) > 50) {
                $errors['name'] = 'Name must be between 2 and 50 characters';
                $hasErrors = true;
            } elseif (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $name)) {
                $errors['name'] = 'Name can only contain letters, numbers, spaces, and hyphens';
                $hasErrors = true;
            }

            if (!$hasErrors) {
                $participant = new Participant();
                $participant->setRaffle($raffle);
                $participant->setUserId(1);
                $participant->setName($name);
                
                $this->entityManager->persist($participant);
                $this->entityManager->flush();

                $this->addFlash('success', 'You have successfully joined the raffle!');
                return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
            }

            // If there are errors, render the form again with error messages
            return $this->render('raffle/join.html.twig', [
                'raffle' => $raffle,
                'form_errors' => $errors
            ]);
        }

        return $this->render('raffle/join.html.twig', [
            'raffle' => $raffle,
            'form_errors' => ['name' => null]
        ]);
    }

    #[Route('/{id}/edit', name: 'app_raffle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Raffle $raffle, SluggerInterface $slugger): Response
    {
        // Check and update raffle status
        $this->checkAndUpdateRaffleStatus($raffle);

        $old = [
            'creator_name' => $raffle->getCreatorName(),
            'start_time' => $raffle->getStartTime()->format('Y-m-d\TH:i'),
            'end_time' => $raffle->getEndTime()->format('Y-m-d\TH:i'),
            'image_name' => $raffle->getImage()
        ];

        if ($request->isMethod('POST')) {
            $errors = [
                'creator_name' => null,
                'image' => null,
                'start_time' => null,
                'end_time' => null
            ];
            $hasErrors = false;
            
            // Keep old values
            $old['creator_name'] = $request->request->get('creator_name');
            $old['start_time'] = $request->request->get('start_time');
            $old['end_time'] = $request->request->get('end_time');
            
            // Validate creator name
            if (empty($old['creator_name'])) {
                $errors['creator_name'] = 'Creator name is required';
                $hasErrors = true;
            } elseif (strlen($old['creator_name']) < 2 || strlen($old['creator_name']) > 50) {
                $errors['creator_name'] = 'Creator name must be between 2 and 50 characters';
                $hasErrors = true;
            }
            
            // Validate start and end times
            $startTime = null;
            $endTime = null;
            $now = new \DateTime();
            
            // Check if dates are provided
            if (empty($old['start_time'])) {
                $errors['start_time'] = 'Start time is required';
                $hasErrors = true;
            }
            
            if (empty($old['end_time'])) {
                $errors['end_time'] = 'End time is required';
                $hasErrors = true;
            }

            if (!empty($old['start_time']) && !empty($old['end_time'])) {
                try {
                    $startTime = new \DateTime($old['start_time']);
                    $endTime = new \DateTime($old['end_time']);
                    
                    // Calculate time difference in minutes
                    $timeDiff = $startTime->diff($endTime);
                    $minutesDiff = ($timeDiff->days * 24 * 60) + ($timeDiff->h * 60) + $timeDiff->i;
                    
                    // Validate end time is at least 1 minute after start time
                    if ($startTime >= $endTime || $minutesDiff < 1) {
                        $errors['start_time'] = 'Start time must be at least 1 minute before end time';
                        $errors['end_time'] = 'End time must be at least 1 minute after start time';
                        $hasErrors = true;
                    }
                } catch (\Exception $e) {
                    $errors['start_time'] = 'Invalid date format';
                    $errors['end_time'] = 'Invalid date format';
                    $hasErrors = true;
                }
            }
            
            // Handle file upload if provided
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                $old['image_name'] = $imageFile->getClientOriginalName();

                try {
                    $imageFile->move(
                        $this->getParameter('raffle_images_directory'),
                        $newFilename
                    );
                    // Delete old image if exists
                    if ($raffle->getImage()) {
                        $oldImagePath = $this->getParameter('raffle_images_directory').'/'.$raffle->getImage();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $raffle->setImage($newFilename);
                } catch (\Exception $e) {
                    $errors['image'] = 'Error uploading image';
                    $hasErrors = true;
                }
            }

            if (!$hasErrors) {
                $raffle->setStartTime($startTime);
                $raffle->setEndTime($endTime);
                $raffle->setCreatorName($old['creator_name']);
                
                $this->entityManager->flush();

                return $this->redirectToRoute('app_raffle_show', ['id' => $raffle->getId()]);
            }

            // If there are errors, render the form again with error messages and old values
            return $this->render('raffle/edit.html.twig', [
                'raffle' => $raffle,
                'form_errors' => $errors,
                'old' => $old
            ]);
        }

        return $this->render('raffle/edit.html.twig', [
            'raffle' => $raffle,
            'form_errors' => [
                'creator_name' => null,
                'image' => null,
                'start_time' => null,
                'end_time' => null
            ],
            'old' => $old
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
