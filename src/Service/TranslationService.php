<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TranslationService
{
    private $httpClient;
    private $rapidApiKey;
    private $rapidApiHost;

    public function __construct(
        HttpClientInterface $httpClient,
        string $rapidApiKey,
        string $rapidApiHost = 'text-translator2.p.rapidapi.com'
    ) {
        $this->httpClient = $httpClient;
        $this->rapidApiKey = $rapidApiKey;
        $this->rapidApiHost = $rapidApiHost;
    }

    public function translate(string $text, string $targetLang = 'fr', string $sourceLang = 'en'): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://' . $this->rapidApiHost . '/translate', [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'X-RapidAPI-Key' => $this->rapidApiKey,
                    'X-RapidAPI-Host' => $this->rapidApiHost,
                ],
                'body' => [
                    'source_language' => $sourceLang,
                    'target_language' => $targetLang,
                    'text' => $text
                ]
            ]);

            $result = $response->toArray();
            return $result['data']['translatedText'] ?? null;
        } catch (\Exception $e) {
            // Log the error in production
            return null;
        }
    }
}