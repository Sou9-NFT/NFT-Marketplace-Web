<?php

namespace App\Twig;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Psr\Log\LoggerInterface;

class ImgurExtension extends AbstractExtension
{
  private $httpClient;
  private $logger;

  public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
  {
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  public function getFunctions(): array
  {
    return [
      new TwigFunction('is_image_accessible', [$this, 'isImageAccessible']),
      new TwigFunction('get_safe_image_url', [$this, 'getSafeImageUrl']),
    ];
  }

  public function isImageAccessible(string $url): bool
  {
    try {
      $response = $this->httpClient->request('HEAD', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          'Referer' => 'https://nftmarketplace.example.com/',
        ],
        'max_duration' => 2, // Set a short timeout
      ]);

      return $response->getStatusCode() === 200;
    } catch (\Exception $e) {
      $this->logger->warning('Failed to check image accessibility: ' . $e->getMessage());
      return false;
    }
  }

  public function getSafeImageUrl(string $url, string $fallbackUrl): string
  {
    if (strpos($url, 'imgur.com') === false) {
      return $url;
    }

    // Try to modify the URL slightly to avoid blocking
    // Sometimes adding a client ID in the URL can help
    if (strpos($url, '?') === false) {
      return $url . '?cb=' . time();
    }

    return $url;
  }
}
