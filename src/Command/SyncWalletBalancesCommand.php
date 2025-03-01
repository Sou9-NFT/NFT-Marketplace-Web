<?php

namespace App\Command;

use App\Entity\User;
use App\Service\EtherscanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncWalletBalancesCommand extends Command
{
    protected static $defaultName = 'app:sync-wallet-balances';

    private $entityManager;
    private $etherscanService;

    public function __construct(EntityManagerInterface $entityManager, EtherscanService $etherscanService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->etherscanService = $etherscanService;
    }

    protected function configure(): void
    {
        $this->setDescription('Syncs user wallet balances with the blockchain');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userRepository = $this->entityManager->getRepository(User::class);
        
        // Get all users with wallet addresses
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.walletAddress IS NOT NULL')
            ->getQuery()
            ->getResult();

        $io->note(sprintf('Found %d users with wallet addresses', count($users)));

        $updatedCount = 0;
        foreach ($users as $user) {
            try {
                $io->text(sprintf('Processing user %s with wallet %s', $user->getEmail(), $user->getWalletAddress()));
                $io->text(sprintf('Current balance in DB: %f', $user->getBalance()));
                
                $blockchainBalance = $this->etherscanService->getTokenBalance($user->getWalletAddress());
                $io->text(sprintf('Blockchain balance: %f', $blockchainBalance));
                
                if (abs($user->getBalance() - $blockchainBalance) > 0.000001) {
                    $io->text('Balance difference detected, updating...');
                    $user->setBalance($blockchainBalance);
                    $this->entityManager->persist($user);
                    $updatedCount++;
                } else {
                    $io->text('Balance matches blockchain (within 0.000001 tolerance)');
                }
            } catch (\Exception $e) {
                $io->error(sprintf('Error updating balance for user %s: %s', $user->getEmail(), $e->getMessage()));
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Successfully synced %d wallet balances', $updatedCount));
        return Command::SUCCESS;
    }
}