<?php

namespace App\Controller\BackOffice;

use App\Entity\Artwork;
use App\Form\ArtworkType;
use App\Repository\ArtworkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/artwork')]
class BackArtworkController extends AbstractController
{
    #[Route('/', name: 'app_back_artwork_index', methods: ['GET'])]
    public function index(ArtworkRepository $artworkRepository): Response
    {
        return $this->render('back_artwork/index.html.twig', [
            'artworks' => $artworkRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_artwork_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $artwork = new Artwork();
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->beginTransaction();
                    
                    $entityManager->persist($artwork);
                    $entityManager->flush();
                    
                    $entityManager->commit();
                    $this->addFlash('success', 'Artwork created successfully.');
                    return $this->redirectToRoute('app_back_artwork_index');
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'An error occurred while saving the artwork. Please try again.');
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('back_artwork/new.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_artwork_show', methods: ['GET'])]
    public function show(Artwork $artwork): Response
    {
        return $this->render('back_artwork/show.html.twig', [
            'artwork' => $artwork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_artwork_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artwork $artwork, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->beginTransaction();
                    $entityManager->flush();
                    $entityManager->commit();
                    
                    $this->addFlash('success', 'Artwork updated successfully.');
                    return $this->redirectToRoute('app_back_artwork_index');
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'An error occurred while updating the artwork. Please try again.');
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('back_artwork/edit.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_artwork_delete', methods: ['POST'])]
    public function delete(Request $request, Artwork $artwork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$artwork->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($artwork);
                $entityManager->flush();
                $this->addFlash('success', 'Artwork deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the artwork.');
            }
        }

        return $this->redirectToRoute('app_back_artwork_index');
    }
}
