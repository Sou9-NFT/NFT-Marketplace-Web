<?php

namespace App\Controller;

use App\Repository\BlogRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog_back')]
class BlogBackController extends AbstractController
{
    #[Route('/', name: 'app_blog_back', methods: ['GET'])]
    public function index(BlogRepository $blogRepository, CommentRepository $commentRepository): Response
    {
        return $this->render('blog_back/blog_back_new.html.twig', [
            'blogs' => $blogRepository->findAll(),
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/posts', name: 'app_posts_index', methods: ['GET'])]
    public function posts(BlogRepository $blogRepository): Response
    {
        return $this->render('blog_back/posts.html.twig', [
            'blogs' => $blogRepository->findAll(),
        ]);
    }

    #[Route('/comments', name: 'app_comments_index', methods: ['GET'])]
    public function comments(CommentRepository $commentRepository): Response
    {
        return $this->render('blog_back/comments.html.twig', [
            'comments' => $commentRepository->findAll(),
        ]);
    }

    #[Route('/posts/{id}', name: 'app_posts_show', methods: ['GET'])]
    public function showPost(BlogRepository $blogRepository, $id): Response
    {
        return $this->render('blog_back/post.html.twig', [
            'blog' => $blogRepository->find($id),
        ]);
    }

    #[Route('/comments/{id}', name: 'app_comments_show', methods: ['GET'])]
    public function showComment(CommentRepository $commentRepository, $id): Response
    {
        return $this->render('blog_back/comment.html.twig', [
            'comment' => $commentRepository->find($id),
        ]);
    }
}
