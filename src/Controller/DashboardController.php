<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $postsCount = $entityManager->getRepository(Blog::class)->count([]);
        $commentsCount = $entityManager->getRepository(Comment::class)->count([]);

        return $this->render('dashboard/index.html.twig', [
            'posts_count' => $postsCount,
            'comments_count' => $commentsCount,
        ]);
    }
}
