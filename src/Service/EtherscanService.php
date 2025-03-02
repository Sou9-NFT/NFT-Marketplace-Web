<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class EtherscanService
{
    private $httpClient;
    private $apiUrl;
    private $apiKey;
    private $contractAddress;
    private $logger;
    private const TOKEN_DECIMALS = 3; // Adjusted to 3 decimals

    public function __construct(
        HttpClientInterface $httpClient,
        string $apiUrl,
        string $apiKey,
        string $contractAddress,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->contractAddress = $contractAddress;
        $this->logger = $logger;
    }

    public function getTokenBalance(string $walletAddress): float
    {
        $this->logger->info('Fetching balance for wallet', [
            'wallet' => $walletAddress,
            'contract' => $this->contractAddress,
            'api_url' => $this->apiUrl
        ]);

        $response = $this->httpClient->request('GET', $this->apiUrl, [
            'query' => [
                'module' => 'account',
                'action' => 'tokenbalance',
                'contractaddress' => $this->contractAddress,
                'address' => $walletAddress,
                'tag' => 'latest',
                'apikey' => $this->apiKey
            ]
        ]);

        $data = $response->toArray();
        $this->logger->info('Etherscan API response', ['response' => $data]);

        if ($data['status'] !== '1') {
            $error = $data['message'] ?? 'Unknown error';
            $this->logger->error('Failed to fetch token balance', [
                'error' => $error,
                'response' => $data
            ]);
            throw new \RuntimeException('Failed to fetch token balance: ' . $error);
        }

        // Convert from smallest unit to token units using the correct number of decimals
        $balance = floatval($data['result']) / pow(10, self::TOKEN_DECIMALS);
        $this->logger->info('Converted balance', [
            'raw' => $data['result'],
            'converted' => $balance,
            'decimals' => self::TOKEN_DECIMALS
        ]);
        
        return $balance;
    }
}