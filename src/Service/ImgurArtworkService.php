<?php

namespace App\Service;

use App\Entity\Artwork;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling artwork image uploads to Imgur
 */
class ImgurArtworkService
{
    private ImgurUploadService $imgurUploadService;
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private LoggerInterface $logger;

    public function __construct(
        ImgurUploadService $imgurUploadService,
        string $targetDirectory,
        SluggerInterface $slugger,
        LoggerInterface $logger
    ) {
        $this->imgurUploadService = $imgurUploadService;
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->logger = $logger;
    }

    /**
     * Process artwork image upload to Imgur
     */
    public function processArtworkImage(Artwork $artwork, $imageFile): void
    {
        // If no file provided but we have an image name that's already an Imgur URL, keep using it
        if (!$imageFile && $artwork->getImageName() && 
            (strpos($artwork->getImageName(), 'imgur.com') !== false || 
             strpos($artwork->getImageName(), 'i.imgur.com') !== false)) {
            return;
        }
        
        // If no file and no image name, nothing to process
        if (!$imageFile) {
            return;
        }
        
        try {
            // Upload to Imgur
            $result = $this->imgurUploadService->uploadImage($imageFile);
            
            if ($result['success']) {
                // Store the Imgur URL as the image name
                $artwork->setImageName($result['url']);
                
                // Store delete hash if available (for future deletion capabilities)
                if (isset($result['deleteHash'])) {
                    // Note: You might want to add a deleteHash property to Artwork entity
                    // For now we'll just use the URL
                }
            } else {
                throw new \Exception('Failed to upload to Imgur: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->logger->error('Imgur upload failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Handle AI-generated images by uploading them to Imgur
     */
    public function handleAiGeneratedImage(string $localPath): ?string
    {
        try {
            $result = $this->imgurUploadService->uploadImage($localPath);
            
            if ($result['success']) {
                // Return the Imgur URL
                return $result['url'];
            }
            
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload AI-generated image to Imgur: ' . $e->getMessage());
            return null;
        }
    }
}
