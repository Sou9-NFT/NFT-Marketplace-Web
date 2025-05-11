<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class ImgurUploadService
{
  private const API_URL = 'https://api.imgur.com/3/image';
  private string $clientId;
  private string $clientSecret;
  private HttpClientInterface $httpClient;
  private LoggerInterface $logger;

  public function __construct(
    ParameterBagInterface $params,
    HttpClientInterface $httpClient,
    LoggerInterface $logger
  ) {
    $this->clientId = $params->get('imgur_client_id');
    $this->clientSecret = $params->get('imgur_client_secret');
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * Upload an image to Imgur
   * 
   * @param UploadedFile|string $image The image file to upload or path to an image
   * @return array{success: bool, url?: string, error?: string}
   */
  public function uploadImage($image): array
  {
    try {
      // Get image content
      if ($image instanceof UploadedFile) {
        $imageContent = file_get_contents($image->getPathname());
      } elseif (is_string($image) && file_exists($image)) {
        $imageContent = file_get_contents($image);
      } else {
        throw new \InvalidArgumentException('Invalid image provided');
      }      // Upload to Imgur
      $response = $this->httpClient->request('POST', self::API_URL, [
        'headers' => [
          'Authorization' => 'Client-ID ' . $this->clientId,
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          'Referer' => 'https://nftmarketplace.example.com/',
          'Origin' => 'https://nftmarketplace.example.com',
          'Accept' => 'application/json',
        ],
        'body' => [
          'image' => base64_encode($imageContent),
          'type' => 'base64',
          'title' => 'NFT Marketplace User Profile',
          'description' => 'Profile picture uploaded from NFT Marketplace'
        ]
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode !== 200) {
        throw new \Exception('Imgur API returned status code: ' . $statusCode);
      }
      $responseData = $response->toArray();
      if (!isset($responseData['data']) || !isset($responseData['data']['link'])) {
        throw new \Exception('Imgur API returned an invalid response');
      }

      // Ensure we use the direct link format (https://i.imgur.com/[id].[ext])
      $imageUrl = $responseData['data']['link'];
      $this->logger->debug('Imgur API response: ' . json_encode($responseData['data']));

      return [
        'success' => true,
        'url' => $imageUrl,
        'deleteHash' => $responseData['data']['deletehash'] ?? null
      ];
    } catch (\Exception $e) {
      $this->logger->error('Failed to upload image to Imgur: ' . $e->getMessage());
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}
