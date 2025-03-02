<?php

namespace App\Controller\BackOffice;

use App\Entity\Artwork;
use App\Form\ArtworkType;
use App\Repository\ArtworkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/artwork')]
class BackArtworkController extends AbstractController
{
    #[Route('/', name: 'app_admin_artwork_index', methods: ['GET'])]
    public function index(ArtworkRepository $artworkRepository): Response
    {
        return $this->render('back_artwork/index.html.twig', [
            'artworks' => $artworkRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_artwork_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $artwork = new Artwork();
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->beginTransaction();
                    
                    // Set the current user as both creator and owner
                    $artwork->setCreator($this->getUser());
                    $artwork->setOwner($this->getUser());
                    
                    $imageFile = $form->get('imageFile')->getData();
                    if ($imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                        try {
                            $imageFile->move(
                                $this->getParameter('artwork_images_directory'),
                                $newFilename
                            );
                            $artwork->setImageName($newFilename);
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Failed to upload file: ' . $e->getMessage());
                            return $this->redirectToRoute('app_admin_artwork_new');
                        }
                    }
                    
                    $entityManager->persist($artwork);
                    $entityManager->flush();
                    
                    $entityManager->commit();
                    $this->addFlash('success', 'Artwork created successfully.');
                    return $this->redirectToRoute('app_admin_artwork_index');
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'An error occurred while saving the artwork: ' . $e->getMessage());
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

    #[Route('/{id}', name: 'app_admin_artwork_show', methods: ['GET'])]
    public function show(Artwork $artwork): Response
    {
        return $this->render('back_artwork/show.html.twig', [
            'artwork' => $artwork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_artwork_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artwork $artwork, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $entityManager->beginTransaction();
                    
                    $imageFile = $form->get('imageFile')->getData();
                    if ($imageFile) {
                        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                        try {
                            // Delete old file if it exists
                            if ($artwork->getImageName()) {
                                $oldFilePath = $this->getParameter('artwork_images_directory').'/'.$artwork->getImageName();
                                if (file_exists($oldFilePath)) {
                                    unlink($oldFilePath);
                                }
                            }
                            
                            $imageFile->move(
                                $this->getParameter('artwork_images_directory'),
                                $newFilename
                            );
                            $artwork->setImageName($newFilename);
                        } catch (FileException $e) {
                            $this->addFlash('error', 'Failed to upload file: ' . $e->getMessage());
                            return $this->redirectToRoute('app_admin_artwork_edit', ['id' => $artwork->getId()]);
                        }
                    }
                    
                    $entityManager->flush();
                    $entityManager->commit();
                    
                    $this->addFlash('success', 'Artwork updated successfully.');
                    return $this->redirectToRoute('app_admin_artwork_index');
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'An error occurred while updating the artwork: ' . $e->getMessage());
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

    #[Route('/{id}', name: 'app_admin_artwork_delete', methods: ['POST'])]
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

        return $this->redirectToRoute('app_admin_artwork_index');
    }
}
