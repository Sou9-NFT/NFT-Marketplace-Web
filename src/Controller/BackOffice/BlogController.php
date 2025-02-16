<?php

namespace App\Controller\BackOffice;

use App\Entity\Blog;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/back/blog')]
#[IsGranted('ROLE_ADMIN')]
class BlogController extends AbstractController
{
    #[Route('/', name: 'app_back_blog_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository): Response
    {
        $blogs = $blogRepository->findBy([], ['date' => 'DESC']);
        return $this->render('blog_back/posts.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/new', name: 'app_back_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $blog = new Blog();
        $blog->setDate(new \DateTime());
        $blog->setUser($this->getUser());
        
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($blog);
            $entityManager->flush();

            $this->addFlash('success', 'Blog post created successfully!');
            return $this->redirectToRoute('app_back_blog_index');
        }

        return $this->render('blog_back/post_new.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Blog post updated successfully!');
            return $this->redirectToRoute('app_back_blog_index');
        }

        return $this->render('blog_back/post_edit.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_back_blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
            $this->addFlash('success', 'Blog post deleted successfully.');
        }

        return $this->redirectToRoute('app_back_blog_index');
    }
}
