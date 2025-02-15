<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home_page');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();
                
                if (!$plainPassword) {
                    $form->get('plainPassword')->addError(new \Symfony\Component\Form\FormError('Please enter a password'));
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                // encode the plain password
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $plainPassword
                    )
                );

                // set the name and initial balance
                $user->setName($form->get('name')->getData());
                $user->setBalance(0);

                $entityManager->persist($user);
                $entityManager->flush();

                // Add flash message for success
                $this->addFlash('success', 'Your account has been created successfully!');

                return $this->redirectToRoute('app_login');
            } else {
                // Add flash message for error
                $this->addFlash('error', 'There were problems with your registration. Please check the form errors below.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
