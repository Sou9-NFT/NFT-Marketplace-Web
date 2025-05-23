<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Service\ProfanityFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    private ProfanityFilter $profanityFilter;

    public function __construct(ProfanityFilter $profanityFilter)
    {
        $this->profanityFilter = $profanityFilter;
    }

    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('back_office_html/comment/index.html.twig', [
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

        // Get the current user
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to add a comment');
        }

        $comment = new Comment();
        $comment->setBlog($blog);
        $comment->setUser($user); // Set the current user
        
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for profanity
            if ($this->profanityFilter->hasProfanity($comment->getContent())) {
                $this->addFlash('error', 'Your comment contains inappropriate content. Please revise.');
                return $this->render('comment/new.html.twig', [
                    'comment' => $comment,
                    'form' => $form,
                    'blog' => $blog,
                ]);
            }

            // Filter content just in case
            $comment->setContent($this->profanityFilter->filter($comment->getContent()));
            
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
            // Check for profanity
            if ($this->profanityFilter->hasProfanity($comment->getContent())) {
                $this->addFlash('error', 'Your comment contains inappropriate content. Please revise.');
                return $this->render('comment/edit.html.twig', [
                    'comment' => $comment,
                    'form' => $form,
                ]);
            }

            // Filter content just in case
            $comment->setContent($this->profanityFilter->filter($comment->getContent()));
            
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

    #[Route('/admin/comments', name: 'app_comment_back_index', methods: ['GET'])]
    public function backendIndex(CommentRepository $commentRepository): Response
    {
        return $this->render('blog_back/comments.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/admin/comments/{id}/show', name: 'app_comment_back_show', methods: ['GET'])]
    public function backendShow(Comment $comment): Response
    {
        return $this->render('blog_back/comment_show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/admin/comments/{id}/edit', name: 'app_comment_back_edit', methods: ['GET', 'POST'])]
    public function backendEdit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check for profanity
            if ($this->profanityFilter->hasProfanity($comment->getContent())) {
                $this->addFlash('error', 'The comment contains inappropriate content. Please revise.');
                return $this->render('blog_back/comment_edit.html.twig', [
                    'comment' => $comment,
                    'form' => $form,
                ]);
            }

            // Filter content just in case
            $comment->setContent($this->profanityFilter->filter($comment->getContent()));
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Comment updated successfully');
            return $this->redirectToRoute('app_comment_back_index');
        }

        return $this->render('blog_back/comment_edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/admin/comments/{id}/delete', name: 'app_comment_back_delete', methods: ['POST'])]
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
