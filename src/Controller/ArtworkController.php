<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Form\ArtworkType;
use App\Repository\ArtworkRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/artwork')]
class ArtworkController extends AbstractController
{
    #[Route('/', name: 'app_artwork_index', methods: ['GET'])]
    public function index(ArtworkRepository $artworkRepository): Response
    {
        return $this->render('artwork/index.html.twig', [
            'artworks' => $artworkRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_artwork_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $artwork = new Artwork();
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $artwork->setImageName($imageFileName);
            }

            $entityManager->persist($artwork);
            $entityManager->flush();

            $this->addFlash('success', 'Artwork created successfully');
            return $this->redirectToRoute('app_artwork_index');
        }

        return $this->render('artwork/new.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_artwork_show', methods: ['GET'])]
    public function show(Artwork $artwork): Response
    {
        return $this->render('artwork/show.html.twig', [
            'artwork' => $artwork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_artwork_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artwork $artwork, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $artwork->setImageName($imageFileName);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Artwork updated successfully');
            return $this->redirectToRoute('app_artwork_index');
        }

        return $this->render('artwork/edit.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_artwork_delete', methods: ['POST'])]
    public function delete(Request $request, Artwork $artwork, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$artwork->getId(), $request->request->get('_token'))) {
            $entityManager->remove($artwork);
            $entityManager->flush();
            $this->addFlash('success', 'Artwork deleted successfully');
        }

        return $this->redirectToRoute('app_artwork_index');
    }
}
