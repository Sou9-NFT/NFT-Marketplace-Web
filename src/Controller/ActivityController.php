<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ActivityController extends AbstractController
{
    private $httpClient;
    private $userRepository;

    public function __construct(HttpClientInterface $httpClient, UserRepository $userRepository)
    {
        $this->httpClient = $httpClient;
        $this->userRepository = $userRepository;
    }

    #[Route('/activity', name: 'app_activity')]
    public function index(): Response
    {
        // Get token address and API key from environment variables
        $tokenAddress = $this->getParameter('etherscan.contract_address');
        $apiKey = $this->getParameter('etherscan.api_key');
        $apiUrl = $this->getParameter('etherscan.api_url');
        
        try {
            // Make API call to Etherscan to get token transfer events
            $response = $this->httpClient->request('GET', $apiUrl, [
                'query' => [
                    'module' => 'account',
                    'action' => 'tokentx',
                    'contractaddress' => $tokenAddress,
                    'page' => 1,
                    'offset' => 20, // Get last 20 transactions
                    'sort' => 'desc',
                    'apikey' => $apiKey
                ]
            ]);

            $data = $response->toArray();
            $transactions = [];

            if (isset($data['status']) && $data['status'] === '1' && isset($data['result'])) {
                foreach ($data['result'] as $tx) {
                    // Format addresses for display (first 3 and last 3 characters)
                    $fromShort = $this->formatAddress($tx['from']);
                    $toShort = $this->formatAddress($tx['to']);
                    
                    // Check if addresses match users in our database
                    $fromUser = $this->userRepository->findOneBy(['walletAddress' => $tx['from']]);
                    $toUser = $this->userRepository->findOneBy(['walletAddress' => $tx['to']]);
                    
                    // Format value with proper decimals
                    $value = bcdiv($tx['value'], bcpow('10', $tx['tokenDecimal']), 2);
                    
                    $transactions[] = [
                        'hash' => $tx['hash'],
                        'from' => $tx['from'],
                        'fromDisplay' => $fromUser ? $fromUser->getName() : $fromShort,
                        'to' => $tx['to'],
                        'toDisplay' => $toUser ? $toUser->getName() : $toShort,
                        'value' => $value,
                        'tokenSymbol' => $tx['tokenSymbol'],
                        'timestamp' => date('Y-m-d H:i:s', $tx['timeStamp']),
                        'blockNumber' => $tx['blockNumber'],
                    ];
                }
            }

            return $this->render('activity/index.html.twig', [
                'transactions' => $transactions,
            ]);
        } catch (\Exception $e) {
            // In case of API error, render the template with empty transactions
            return $this->render('activity/index.html.twig', [
                'transactions' => [],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Format an address to show just first and last 3 characters
     */
    private function formatAddress(string $address): string
    {
        if (strlen($address) <= 8) {
            return $address;
        }
        
        return substr($address, 0, 4) . '...' . substr($address, -4);
    }
}