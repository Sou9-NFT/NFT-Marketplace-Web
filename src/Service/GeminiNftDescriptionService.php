<?php

namespace App\Service;

use App\Entity\Artwork;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeminiNftDescriptionService
{
    private $apiKey;
    private $httpClient;
    private $params;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent';

    // NFT style descriptors to enhance prompts
    private const NFT_STYLES = [
        'pixel art', 'abstract', 'cyberpunk', 'vaporwave', 'surrealist', 'minimalist',
        'retro', 'futuristic', 'psychedelic', 'glitch art', '3D', 'hand-drawn',
        'photorealistic', 'anime', 'watercolor', 'pop art', 'digital illustration'
    ];

    // Funny tones to vary the humor style
    private const HUMOR_TONES = [
        'absurdist', 'satirical', 'whimsical', 'punny', 'sarcastic', 'ironic',
        'memetic', 'fourth-wall breaking', 'meta', 'self-referential', 'exaggerated'
    ];

    // Personality traits for the NFT
    private const NFT_PERSONALITIES = [
        'pretentious', 'philosophical', 'confused', 'overconfident', 'dramatic',
        'melodramatic', 'existential', 'neurotic', 'sassy', 'clueless', 'pompous',
        'cheerful', 'timid', 'paranoid', 'hipster', 'fashionable', 'trendy'
    ];

    public function __construct(
        HttpClientInterface $httpClient,
        string $geminiApiKey,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
        $this->params = $params;
    }

    /**
     * Generate a funny description for an NFT
     * 
     * @param Artwork $artwork The artwork entity
     * @param string|null $style Optional style hint
     * @return string The generated description
     */
    public function generateNftDescription(Artwork $artwork, ?string $style = null): string
    {
        try {
            $title = $artwork->getTitle();
            $category = $artwork->getCategory() ? $artwork->getCategory()->getName() : 'art';
            $creator = $artwork->getCreator() ? $artwork->getCreator()->getName() : 'an anonymous artist';
            
            // Choose random style elements to enhance prompt if none provided
            $nftStyle = $style ?? self::NFT_STYLES[array_rand(self::NFT_STYLES)];
            $humorTone = self::HUMOR_TONES[array_rand(self::HUMOR_TONES)];
            $personality = self::NFT_PERSONALITIES[array_rand(self::NFT_PERSONALITIES)];

            // Create a rich prompt with context
            $prompt = "Generate a funny, {$humorTone} description for an NFT titled \"{$title}\" in the {$category} category. " .
                     "The NFT is in a {$nftStyle} style created by {$creator}. " .
                     "Make the description entertaining as if the NFT had a {$personality} personality. " .
                     "Include a ridiculous backstory, an exaggerated value proposition, and a humorous tagline. " .
                     "Keep it between 100-150 words and make crypto enthusiasts laugh.";

            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.9,  // Higher temperature for more creativity
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 300,
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $description = $data['candidates'][0]['content']['parts'][0]['text'];
                return trim($description);
            }
            
            // Fallback if API response is invalid
            return $this->generateFallbackDescription($title, $category);
            
        } catch (\Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            return $this->generateFallbackDescription($title, $category);
        }
    }

    /**
     * Generate a description for an NFT based on its title and image
     * 
     * @param Artwork $artwork The artwork entity
     * @return string The generated description
     */
    public function generateDescription(Artwork $artwork): string
    {
        try {
            $title = $artwork->getTitle();
            $category = $artwork->getCategory() ? $artwork->getCategory()->getName() : 'art';
            
            // Get the actual image file
            $imagePath = $this->getArtworkImagePath($artwork);
            
            if (!$imagePath || !file_exists($imagePath)) {
                // If image doesn't exist, fall back to text-only description
                return $this->generateTextOnlyDescription($artwork);
            }
            
            // Read image and encode as base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            
            // Create the request body with text and image parts
            $requestBody = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Generate a creative, funny description for an NFT titled \"$title\" in the $category category. The image is attached. Make the description entertaining and appealing to crypto enthusiasts."
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 1.0,
                    'maxOutputTokens' => 300
                ]
            ];

            $response = $this->httpClient->request('POST', self::API_URL . '?key=' . $this->apiKey, [
                'json' => $requestBody
            ]);

            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
            
            // Fallback if API response is invalid
            return "A stunning NFT titled \"$title\" in the $category category. Own this unique digital asset today!";
            
        } catch (\Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            return "A stunning NFT titled \"$title\" in the $category category. Own this unique digital asset today!";
        }
    }

    /**
     * Get the full filesystem path to the artwork image
     */
    private function getArtworkImagePath(Artwork $artwork): ?string
    {
        $imageName = $artwork->getImageName();
        if (!$imageName) {
            return null;
        }
        
        // Construct path to image based on your upload directory configuration
        $uploadDir = $this->params->get('artwork_directory');
        return $uploadDir . '/' . $imageName;
    }
    
    /**
     * Generate text-only description when image is unavailable
     */
    private function generateTextOnlyDescription(Artwork $artwork): string
    {
        $title = $artwork->getTitle();
        $category = $artwork->getCategory() ? $artwork->getCategory()->getName() : 'art';
        
        try {
            $response = $this->httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent' . '?key=' . $this->apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => "Generate a creative, funny description for an NFT titled \"$title\" in the $category category. Make the description entertaining and appealing to crypto enthusiasts."
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 1.0,
                        'maxOutputTokens' => 300
                    ]
                ]
            ]);
            
            $data = $response->toArray();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
        } catch (\Exception $e) {
            error_log('Text-only Gemini API Error: ' . $e->getMessage());
        }
        
        return "A stunning NFT titled \"$title\" in the $category category. Own this unique digital asset today!";
    }

    /**
     * Generate a fallback description if the API call fails
     */
    private function generateFallbackDescription(string $title, string $category): string
    {
        $descriptions = [
            "Behold \"{$title}\"! The NFT that's so exclusive, even its pixels don't know what they're doing. Created during a cosmic alignment of keyboard smashing and energy drinks, this {$category} masterpiece will absolutely, positively... exist in your wallet. Experts call it \"indescribable\" (mostly because they're speechless). Own it today, confuse your friends forever!",
            
            "Welcome to the absurd world of \"{$title}\"! This {$category} NFT wasn't just created—it escaped. Some say it whispers crypto tips at night, others claim it's actually a digital hamster in disguise. Either way, it's guaranteed to make your collection 103% more interesting. Warning: May cause spontaneous bragging to strangers.",
            
            "Meet \"{$title}\", the {$category} NFT that thinks it's worth more than your house! Crafted from the finest digital nonsense and sprinkled with pretentious artistic jargon, this masterpiece radiates pure, unfiltered \"I bought this before it was cool\" energy. Perfect for impressing people who don't understand NFTs either!",
            
            "Introducing \"{$title}\"! Is it art? Is it an investment? Is it just expensive pixels? Yes! This extraordinary {$category} piece challenges the very concept of value by making you question why you're spending actual money on it. Comes with free existential crisis and automatic conversation starter at awkward dinner parties.",
            
            "Ah, \"{$title}\"—the {$category} NFT equivalent of that obscure band you pretend to like! Crafted with exclusive algorithms of randomness, it's simultaneously meaningful and meaningless. Own this digital contradiction and watch your friends nod thoughtfully while secretly wondering if they're missing something. Confusion guaranteed or your ETH back!"
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    /**
     * Generate descriptions in bulk for multiple artworks
     */
    public function generateBulkDescriptions(array $artworks): array
    {
        $results = [];
        foreach ($artworks as $artwork) {
            $results[$artwork->getId()] = $this->generateNftDescription($artwork);
        }
        return $results;
    }

    
}