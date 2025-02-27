<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private $httpClient;
    private const API_URL = 'https://api.mymemory.translated.net/get';

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function translate(string $text, string $targetLang = 'fr', string $sourceLang = 'en'): ?string
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'q' => $text,
                    'langpair' => $sourceLang . '|' . $targetLang
                ]
            ]);

            $result = $response->toArray();
            // For debugging purposes
            dump($result);
            return $result['responseData']['translatedText'] ?? null;
        } catch (\Exception $e) {
            // Log the error in production
            return null;
        }
    }
}
