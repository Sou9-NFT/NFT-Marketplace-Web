<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class StabilityAIService
{
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    /**
     * Generate an image from a text prompt using Stability AI
     *
     * @param string $prompt The text prompt
     * @param array $options Additional options for the API
     * @return array Response with success/error information and data
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        $this->logger->info('Starting image generation with prompt: ' . substr($prompt, 0, 50));

        try {
            // Set up cURL request
            $ch = curl_init();
            $url = 'https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            // Set headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Default request parameters
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

            // Override defaults with any provided options
            if (isset($options['cfg_scale'])) $requestData['cfg_scale'] = $options['cfg_scale'];
            if (isset($options['height'])) $requestData['height'] = $options['height'];
            if (isset($options['width'])) $requestData['width'] = $options['width'];
            if (isset($options['samples'])) $requestData['samples'] = $options['samples'];
            if (isset($options['steps'])) $requestData['steps'] = $options['steps'];

            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

            // Log request details
            $this->logger->debug('Sending request to: ' . $url);
            $this->logger->debug('Request data: ' . json_encode($requestData));

            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // Log response code
            $this->logger->debug('API response code: ' . $httpCode);

            curl_close($ch);

            if ($error) {
                $this->logger->error('cURL Error: ' . $error);
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $error
                ];
            }

            if ($httpCode !== 200) {
                $responseData = json_decode($response, true);
                $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'API returned status code ' . $httpCode;
                
                // Log detailed error information
                $this->logger->error('API Error: ' . $errorMessage);
                if (isset($responseData['errors'])) {
                    $this->logger->error('API Error details: ' . json_encode($responseData['errors']));
                }
                
                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'response' => $responseData
                ];
            }

            $responseData = json_decode($response, true);
            
            if (!isset($responseData['artifacts']) || empty($responseData['artifacts'])) {
                $this->logger->error('No image was generated in response');
                return [
                    'success' => false,
                    'error' => 'No image was generated in response'
                ];
            }

            $this->logger->info('Successfully generated image');
            
            return [
                'success' => true,
                'base64Image' => $responseData['artifacts'][0]['base64'],
                'response' => $responseData
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Exception during image generation: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
