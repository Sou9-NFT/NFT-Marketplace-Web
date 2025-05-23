<?php

namespace App\Controller\BackOffice;

use App\Entity\User;
use App\Form\BackOffice\UserType;
use App\Repository\UserRepository;
use App\Service\ImgurUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $imgurUploadService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ImgurUploadService $imgurUploadService
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->imgurUploadService = $imgurUploadService;
    }

    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('back_user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
    #[Route('/new', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The plainPassword is now directly mapped to the entity
            // Just need to hash it and set the password
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);

            // Set creation date
            $user->setCreatedAt(new \DateTimeImmutable());

            // Handle profile picture upload if provided
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $result = $this->imgurUploadService->uploadImage($profilePictureFile);

                if ($result['success']) {
                    $user->setProfilePicture($result['url']);
                } else {
                    $this->addFlash('error', 'Failed to upload profile picture: ' . ($result['error'] ?? 'Unknown error'));
                }
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('back_user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('back_user/show.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/{id}/edit', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle profile picture upload if provided
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $result = $this->imgurUploadService->uploadImage($profilePictureFile);

                if ($result['success']) {
                    $user->setProfilePicture($result['url']);
                } else {
                    $this->addFlash('error', 'Failed to upload profile picture: ' . ($result['error'] ?? 'Unknown error'));
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('back_user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/roles', name: 'app_admin_user_roles', methods: ['GET', 'POST'])]
    public function roles(Request $request, User $user): Response
    {
        $availableRoles = [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_SELLER',
            'ROLE_AUTHOR'
        ];

        if ($request->isMethod('POST')) {
            $roles = $request->request->all()['roles'] ?? [];
            // Ensure ROLE_USER is always present
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            $user->setRoles($roles);
            $this->entityManager->flush();

            $this->addFlash('success', 'User roles updated successfully.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('back_user/roles.html.twig', [
            'user' => $user,
            'available_roles' => $availableRoles,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
