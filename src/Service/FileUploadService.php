<?php

namespace App\Service;

use App\Entity\Artwork;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }

        return $fileName;
    }
    
    /**
     * Handle existing file - no upload needed, just verify it exists
     */
    public function handleExistingFile(string $filename): bool
    {
        $filePath = $this->targetDirectory . '/' . $filename;
        return file_exists($filePath);
    }

    public function processArtworkImage(Artwork $artwork, $imageFile): void
    {
        // If no file provided but we have an image name, it could be an AI-generated image
        if (!$imageFile && $artwork->getImageName()) {
            // Check if the file exists in the target directory
            if ($this->handleExistingFile($artwork->getImageName())) {
                return; // File exists, no need to process further
            }
        }
        
        // If no file and no image name, nothing to process
        if (!$imageFile) {
            return;
        }
        
        // Delete old file if it exists (but only if different from current)
        $this->deleteOldFile($artwork);
        
        // Handle different file types
        if ($imageFile instanceof UploadedFile) {
            // Regular uploaded file
            $newFilename = $this->upload($imageFile);
            $artwork->setImageName($newFilename);
        } elseif ($imageFile instanceof File) {
            // File object (like from AI generation)
            $newFilename = basename($imageFile->getPathname());
            
            // If file is not already in the target directory, copy it there
            if (!$this->handleExistingFile($newFilename)) {
                $targetPath = $this->targetDirectory . '/' . $newFilename;
                copy($imageFile->getPathname(), $targetPath);
            }
            
            $artwork->setImageName($newFilename);
        } elseif (is_string($imageFile) && $this->handleExistingFile($imageFile)) {
            // String filename that exists
            $artwork->setImageName($imageFile);
        }
    }
    
    public function deleteOldFile(Artwork $artwork): void
    {
        if ($artwork->getImageName()) {
            $filePath = $this->targetDirectory.'/'.$artwork->getImageName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
