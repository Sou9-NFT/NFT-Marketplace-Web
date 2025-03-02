<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PurgoMalumService
{
    private const API_URL = 'https://www.purgomalum.com/service/json';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function containsProfanity(string $text): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'text' => $text
                ]
            ]);

            $data = $response->toArray();
            
            // If the result is different from the input, it means profanity was found and replaced
            return $data['result'] !== $text;
        } catch (\Exception $e) {
            // If API call fails, log the error and return false to allow the text
            // In production, you might want to handle this differently
            return false;
        }
    }
}