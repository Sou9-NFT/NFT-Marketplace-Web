<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\BlogType;
use App\Form\CommentType;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    #[Route(name: 'app_blog_index', methods: ['GET', 'POST'])]
    public function index(Request $request, BlogRepository $blogRepository, EntityManagerInterface $entityManager): Response
    {
        $blogs = $blogRepository->findAll();
        $comment_forms = [];

        foreach ($blogs as $blog) {
            $comment = new Comment();
            $comment->setBlog($blog);
            $form = $this->createForm(CommentType::class, $comment, [
                'action' => $this->generateUrl('app_blog_add_comment', ['id' => $blog->getId()])
            ]);
            $comment_forms[$blog->getId()] = $form->createView();
        }

        return $this->render('blog/index.html.twig', [
            'blogs' => $blogs,
            'comment_forms' => $comment_forms,
        ]);
    }

    #[Route('/{id}/comment', name: 'app_blog_add_comment', methods: ['POST'])]
    public function addComment(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setBlog($blog);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Your comment has been added successfully!');
        }

        return $this->redirectToRoute('app_blog_index');
    }

    #[Route('/new', name: 'app_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $blog = new Blog();
        $blog->setDate(new \DateTime()); // Set current date automatically
        
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($blog);
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_show', methods: ['GET'])]
    public function show(Blog $blog): Response
    {
        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/comment', name: 'app_blog_add_comment_to_blog', methods: ['POST'])]
    public function addCommentToBlog(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $comment->setBlog($blog);
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Your comment has been added successfully!');
            return $this->redirectToRoute('app_blog_show', ['id' => $blog->getId()]);
        }

        return $this->render('blog/readBlog.html.twig', [
            'blog' => $blog,
            'comment_form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_blog_delete', methods: ['POST'])]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_blog_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/back/posts', name: 'app_blog_back_posts', methods: ['GET'])]
    public function backendPosts(BlogRepository $blogRepository): Response
    {
        $blogs = $blogRepository->findBy([], ['date' => 'DESC']);
        return $this->render('blog_back/posts.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/back/posts/{id}/edit', name: 'app_blog_back_edit', methods: ['GET', 'POST'])]
    public function backendEdit(Request $request, Blog $blog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_blog_back_posts');
        }

        return $this->render('blog_back/post_edit.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }
}