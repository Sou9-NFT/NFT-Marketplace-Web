<?php

namespace App\Twig;

use App\Service\EthereumService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EthereumExtension extends AbstractExtension
{
    private $ethereumService;

    public function __construct(EthereumService $ethereumService)
    {
        $this->ethereumService = $ethereumService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_eth_balance', [$this, 'getEthBalance']),
            new TwigFunction('get_token_balance', [$this, 'getTokenBalance']),
        ];
    }

    public function getEthBalance(): string
    {
        try {
            $balance = $this->ethereumService->getBalance($this->ethereumService->getContractAddress());
            // Convert Wei to Sepolia ETH
            return number_format($balance / 1e18, 4) . ' SEP';
        } catch (\Exception $e) {
            return '0.0000 SEP';
        }
    }

    public function getTokenBalance(): string
    {
        try {
            $balance = $this->ethereumService->getTokenBalance($this->ethereumService->getContractAddress());
            return number_format($balance, 0) . ' ESPRIT DAN';
        } catch (\Exception $e) {
            return '0 ESPRIT DAN';
        }
    }
}