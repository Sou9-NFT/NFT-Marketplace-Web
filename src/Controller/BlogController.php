<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blogs', name: 'app_blog')]
    public function index(): Response
    {
        return $this->render('blog/blogs.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/readBlog', name: 'app_blog')]
    public function readBlog(): Response
    {
        return $this->render('blog/readBlog.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }

    #[Route('/editBlog', name: 'app_blog')]
    public function editBlog(): Response
    {
        return $this->render('blog/editBlog.html.twig', [
            'controller_name' => 'BlogController',
        ]);
    }
}
