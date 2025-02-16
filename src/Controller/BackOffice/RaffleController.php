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

#[Route('/back/raffle')]
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

    #[Route('/', name: 'app_back_raffle_index', methods: ['GET'])]
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

    #[Route('/new', name: 'app_back_raffle_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $raffle = new Raffle();
        $form = $this->createForm(RaffleType::class, $raffle);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
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
                    return $this->redirectToRoute('app_back_raffle_new');
                }
            }
    
            $raffle->setStartTime(new \DateTime('now'));
            $raffle->setCreator($this->getUser());
            $raffle->setStatus('active');
            $this->entityManager->persist($raffle);
            $this->entityManager->flush();

            $this->addFlash('success', 'Raffle created successfully.');
            return $this->redirectToRoute('app_back_raffle_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('raffle/raffle_form_back.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_raffle_show', methods: ['GET'])]
    public function show(Raffle $raffle): Response
    {
        $this->checkAndUpdateRaffleStatus($raffle);
        
        return $this->render('raffle/raffleback.html.twig', [
            'raffle' => $raffle,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_raffle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Raffle $raffle, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(RaffleType::class, $raffle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
                    return $this->redirectToRoute('app_back_raffle_edit', ['id' => $raffle->getId()]);
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Raffle updated successfully.');
            return $this->redirectToRoute('app_back_raffle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('raffle/raffle_form_back.html.twig', [
            'raffle' => $raffle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_back_raffle_delete', methods: ['POST'])]
    public function delete(Request $request, Raffle $raffle): Response
    {
        if ($this->isCsrfTokenValid('delete'.$raffle->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($raffle);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_back_raffle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/participants', name: 'app_back_raffle_participants', methods: ['GET'])]
    public function participants(Raffle $raffle): Response
    {
        return $this->render('raffle/raffleback.html.twig', [
            'raffle' => $raffle,
            'participants' => $raffle->getParticipants(),
        ]);
    }
}
