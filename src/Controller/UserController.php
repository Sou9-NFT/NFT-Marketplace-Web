<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ImgurUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class UserController extends AbstractController
{
    private $entityManager;
    private $imgurUploadService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ImgurUploadService $imgurUploadService
    ) {
        $this->entityManager = $entityManager;
        $this->imgurUploadService = $imgurUploadService;
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->save($user, true);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'canEdit' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() === $user->getId(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, User $user, UserRepository $userRepository, SluggerInterface $slugger): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
            throw new AccessDeniedException('You can only edit your own profile.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $profilePictureFile = $form->get('profilePicture')->getData();

            if ($profilePictureFile) {
                $form->isValid();
            } else {
                if ($form->isValid()) {
                    try {
                        $userRepository->save($user, true);
                        return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Error updating profile.');
                    }
                }
            }
            if ($profilePictureFile) {
                // Upload to Imgur instead of local storage
                $result = $this->imgurUploadService->uploadImage($profilePictureFile);

                if ($result['success']) {
                    // Store the Imgur URL in the database
                    $user->setProfilePicture($result['url']);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    $userRepository->save($user, true);

                    $this->addFlash('success', 'Profile picture uploaded successfully.');
                    return $this->redirectToRoute('app_user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
                } else {
                    $this->addFlash('error', 'Failed to upload profile picture: ' . ($result['error'] ?? 'Unknown error'));
                }
            }
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
