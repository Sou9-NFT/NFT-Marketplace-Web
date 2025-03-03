<?php

namespace App\Controller;

use App\Entity\Artwork;
use App\Form\ArtworkType;
use App\Repository\ArtworkRepository;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/artwork')]
class ArtworkController extends AbstractController
{
    private ?EntityManagerInterface $entityManager;
    private FileUploadService $fileUploadService;

    public function __construct(
        EntityManagerInterface $entityManager = null,
        FileUploadService $fileUploadService
    ) {
        $this->entityManager = $entityManager;
        $this->fileUploadService = $fileUploadService;
    }
    
    /**
     * Get the entity manager
     */
    private function getEntityManager(): EntityManagerInterface
    {
        if (!$this->entityManager) {
            throw new \LogicException('Entity manager not initialized.');
        }
        return $this->entityManager;
    }

    #[Route('/', name: 'app_artwork_index', methods: ['GET'])]
    public function index(Request $request, ArtworkRepository $artworkRepository): Response
    {
        $searchTerm = $request->query->get('search');
        $sortBy = $request->query->get('sort', 'date');
        $direction = $request->query->get('direction', 'DESC');
        
        // Use the existing searchByTerm method from ArtworkRepository
        $artworks = $artworkRepository->searchByTerm($searchTerm, $sortBy, $direction);
        
        return $this->render('artwork/index.html.twig', [
            'artworks' => $artworks,
            'searchTerm' => $searchTerm,
            'sortBy' => $sortBy,
            'direction' => $direction
        ]);
    }

    #[Route('/new', name: 'app_artwork_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Use the entity manager passed as method parameter instead of the class property
        $this->entityManager = $entityManager;
        
        // Check if user is authenticated before creating artwork
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('You must be logged in to create artwork.');
        }

        $artwork = new Artwork();
        
        // Check if we need to use an AI-generated image
        $aiImagePath = $request->query->get('aiImage');
        $aiImageUsed = false;
        
        if ($aiImagePath) {
            $fullPath = $this->getParameter('artwork_images_directory') . '/' . basename($aiImagePath);
            if (file_exists($fullPath)) {
                $aiFile = new File($fullPath);
                $artwork->setImageFile($aiFile);
                $artwork->setImageName(basename($aiImagePath));
                $aiImageUsed = true;
                
                // Store AI image info in session
                $request->getSession()->set('ai_generated_image', [
                    'path' => $aiImagePath,
                    'filename' => basename($aiImagePath)
                ]);
            }
        }
        
        // Create form after setting AI image (so form builder can check for AI image)
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set creator/owner before starting transaction
                $artwork->setCreator($this->getUser());
                $artwork->setOwner($this->getUser());
                // Remove the status activation
                // $artwork->activate(); // Set status to active
                
                $this->getEntityManager()->beginTransaction();
                
                // Handle image file (regular upload OR AI-generated)
                if ($aiImageUsed) {
                    // AI-generated image was already saved to disk, just make sure the name is set
                    if (!$artwork->getImageName()) {
                        $artwork->setImageName(basename($aiImagePath));
                    }
                } else {
                    // Regular file upload
                    $imageFile = $form->has('imageFile') ? $form->get('imageFile')->getData() : null;
                    if ($imageFile) {
                        $this->fileUploadService->processArtworkImage($artwork, $imageFile);
                    }
                }

                $this->getEntityManager()->persist($artwork);
                $this->getEntityManager()->flush();
                
                $this->getEntityManager()->commit();
                
                // Clear any stored AI image data from session
                $request->getSession()->remove('ai_generated_image');
                
                $this->addFlash('success', 'Artwork created successfully.');
                return $this->redirectToRoute('app_artwork_index');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                $this->addFlash('error', 'An error occurred while saving the artwork: ' . $e->getMessage());
            }
        }

        return $this->render('artwork/new.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
            'aiImageUsed' => $aiImageUsed
        ]);
    }

    #[Route('/generate', name: 'app_artwork_generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        if (!$request->isXmlHttpRequest() && $request->query->get('debug') !== 'true') {
            return $this->json(['error' => 'Invalid request'], 400);
        }
        
        $prompt = $request->request->get('prompt');
        if (empty($prompt)) {
            return $this->json(['success' => false, 'error' => 'Prompt cannot be empty'], 400);
        }
        
        // Stability AI API integration
        try {
            // Get API key directly from .env using $_ENV or $_SERVER to avoid any parameter substitution issues
            $apiKey = $_ENV['STABILITY_AI_API_KEY'] ?? $_SERVER['STABILITY_AI_API_KEY'] ?? null;
            
            if (empty($apiKey) || $apiKey === '%env(STABILITY_AI_API_KEY)%') {
                throw new \Exception('Stability AI API key is not properly configured. Please check your .env file.');
            }
            
            // Hard code the API key for now since we know what it is
            $apiKey = 'sk-7gjnamCmIJr3Ne4rHoKbx3e6IGCXsDGa1yQssDdViEwWP4MZ';
            
            // Set up cURL request to Stability AI
            $ch = curl_init();
            $url = 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_VERBOSE, false); // Change to false to reduce verbosity
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Increase timeout to 60 seconds
            
            // Set headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $apiKey,
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            // Prepare request body
            $requestData = [
                'text_prompts' => [
                    [
                        'text' => $prompt,
                        'weight' => 1.0
                    ]
                ],
                'cfg_scale' => 7,
                'height' => 1024,
                'width' => 1024,
                'samples' => 1,
                'steps' => 30,
            ];
            
            $requestJson = json_encode($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestJson);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            // Handle errors
            if ($error) {
                throw new \Exception('cURL Error: ' . $error);
            }
            
            if ($httpCode !== 200) {
                $responseData = json_decode($response, true);
                $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'API returned status code ' . $httpCode;
                
                // Add detailed error information
                if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errorMessage .= '. Errors: ' . json_encode($responseData['errors']);
                }
                
                throw new \Exception($errorMessage);
            }
            
            // Process successful response
            $responseData = json_decode($response, true);
            
            if (!isset($responseData['artifacts']) || empty($responseData['artifacts'])) {
                throw new \Exception('No image was generated in response');
            }
            
            // Get the base64 image data from the first artifact
            $base64Image = $responseData['artifacts'][0]['base64'];
            
            // Save the image to your server
            $imageData = base64_decode($base64Image);
            $filename = 'stability-ai-' . uniqid() . '.png';
            $imagePath = $this->getParameter('artwork_images_directory') . '/' . $filename;
            
            if (file_put_contents($imagePath, $imageData) === false) {
                throw new \Exception('Failed to save generated image');
            }
            
            // Create a real file object from the saved file
            $file = new File($imagePath);
            
            // Store the AI image info in session
            $request->getSession()->set('ai_generated_image', [
                'path' => '/uploads/artworks/' . $filename,
                'filename' => $filename
            ]);
            
            // Return success response with the image details
            return $this->json([
                'success' => true,
                'filename' => $filename,
                'path' => '/uploads/artworks/' . $filename,
                'message' => 'Image generated successfully'
            ]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'AI Generation Error: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'Failed to generate artwork: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/test-api', name: 'app_stability_api_test', methods: ['GET'])]
    public function testStabilityApi(): Response
    {
        // Simple test page to manually verify API functionality
        return $this->render('artwork/test_api.html.twig', [
            'api_key' => $this->getParameter('stability_ai_api_key'),
        ]);
    }

    #[Route('/{id}', name: 'app_artwork_show', methods: ['GET'])]
    public function show(Artwork $artwork): Response
    {
        return $this->render('artwork/show.html.twig', [
            'artwork' => $artwork,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_artwork_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artwork $artwork, EntityManagerInterface $entityManager): Response
    {
        // Use the entity manager passed as method parameter
        $this->entityManager = $entityManager;
        
        $form = $this->createForm(ArtworkType::class, $artwork);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                try {
                    $this->getEntityManager()->beginTransaction();
                    
                    $imageFile = $form->get('imageFile')->getData();
                    if ($imageFile) {
                        // Use the file upload service instead of handling it directly
                        $this->fileUploadService->processArtworkImage($artwork, $imageFile);
                    }
                    
                    $this->getEntityManager()->flush();
                    $this->getEntityManager()->commit();
                    
                    $this->addFlash('success', 'Artwork updated successfully.');
                    return $this->redirectToRoute('app_artwork_index');
                } catch (\Exception $e) {
                    $this->getEntityManager()->rollback();
                    $this->addFlash('error', 'An error occurred while updating the artwork: ' . $e->getMessage());
                }
            } else {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('artwork/edit.html.twig', [
            'artwork' => $artwork,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_artwork_delete', methods: ['POST'])]
    public function delete(Request $request, Artwork $artwork, EntityManagerInterface $entityManager): Response
    {
        // Use the entity manager passed as method parameter
        $this->entityManager = $entityManager;
        
        if ($this->isCsrfTokenValid('delete'.$artwork->getId(), $request->request->get('_token'))) {
            try {
                // Use the file upload service to delete the file
                $this->fileUploadService->deleteOldFile($artwork);
                
                $this->getEntityManager()->remove($artwork);
                $this->getEntityManager()->flush();
                $this->addFlash('success', 'Artwork deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while deleting the artwork: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_artwork_index');
    }

    #[Route('/clear-ai-image', name: 'app_artwork_clear_ai_image', methods: ['POST'])]
    public function clearAiImage(Request $request): JsonResponse
    {
        // Remove AI image info from session
        $request->getSession()->remove('ai_generated_image');
        
        return new JsonResponse([
            'success' => true
        ]);
    }
}
