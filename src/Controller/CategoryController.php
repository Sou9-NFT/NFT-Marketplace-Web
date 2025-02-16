<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($category->getType()) {
                    $category->setAllowedMimeTypes(Category::getAvailableMimeTypes($category->getType()));
                }
                
                $entityManager->persist($category);
                $entityManager->flush();

                $this->addFlash('success', 'Category created successfully.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating category: ' . $e->getMessage());
            }
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($category->getType()) {
                    $category->setAllowedMimeTypes(Category::getAvailableMimeTypes($category->getType()));
                }
                
                $entityManager->flush();

                $this->addFlash('success', 'Category updated successfully.');
                return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating category: ' . $e->getMessage());
            }
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/info', name: 'app_category_info', methods: ['GET'])]
    public function getCategoryInfo(Request $request, ?Category $category = null): JsonResponse
    {
        try {
            $type = $request->query->get('type');
            
            if ($category === null && $type) {
                if (!in_array($type, [Category::TYPE_IMAGE, Category::TYPE_VIDEO, Category::TYPE_AUDIO])) {
                    throw new \InvalidArgumentException('Invalid category type');
                }
                
                return $this->json([
                    'type' => $type,
                    'allowedMimeTypes' => Category::getAvailableMimeTypes($type)
                ]);
            }

            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }

            return $this->json([
                'id' => $category->getId(),
                'type' => $category->getType(),
                'description' => $category->getDescription(),
                'allowedMimeTypes' => $category->getAllowedMimeTypes(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], $e instanceof NotFoundHttpException ? 404 : 400);
        }
    }
}
