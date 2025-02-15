<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/new/{blogId}', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, int $blogId): Response
    {
        $blog = $entityManager->getRepository(Blog::class)->find($blogId);
        
        if (!$blog) {
            throw $this->createNotFoundException('Blog post not found');
        }

        $comment = new Comment();
        $comment->setBlog($blog);
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Your comment has been added successfully!');
            return $this->redirectToRoute('app_blog_index');
        }

        return $this->render('comment/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_blog_show', ['id' => $comment->getBlog()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->getPayload()->getString('_token'))) {
            $blogId = $comment->getBlog()->getId();
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Comment deleted successfully');
            return $this->redirectToRoute('app_blog_show', ['id' => $blogId]);
        }

        return $this->redirectToRoute('app_blog_index');
    }

    #[Route('/back/comments', name: 'app_comment_back_index', methods: ['GET'])]
    public function backendIndex(CommentRepository $commentRepository): Response
    {
        return $this->render('blog_back/comments.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/back/comments/{id}/edit', name: 'app_comment_back_edit', methods: ['GET', 'POST'])]
    public function backendEdit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Comment updated successfully');
            return $this->redirectToRoute('app_comment_back_index');
        }

        return $this->render('blog_back/comment_edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/back/comments/{id}/delete', name: 'app_comment_back_delete', methods: ['POST'])]
    public function backendDelete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
           /*  $this->addFlash('success', 'Comment deleted successfully'); */
            return $this->redirectToRoute('app_comment_back_index');
        }

        return $this->redirectToRoute('app_comment_back_index');
    }
}
