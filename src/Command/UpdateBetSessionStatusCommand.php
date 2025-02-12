<?php

namespace App\Command;

use App\Repository\BetSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-bet-session-status',
    description: 'Updates the status of BetSession entities based on the current date and time',
)]
class UpdateBetSessionStatusCommand extends Command
{
    protected static $defaultName = 'app:update-bet-session-status';
    private $betSessionRepository;
    private $entityManager;

    public function __construct(BetSessionRepository $betSessionRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->betSessionRepository = $betSessionRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Updates the status of BetSession entities based on the current date and time');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $betSessions = $this->betSessionRepository->findAll();

        foreach ($betSessions as $betSession) {
            $betSession->updateStatus();
            $this->entityManager->persist($betSession);
        }

        $this->entityManager->flush();

        $io->success('BetSession statuses have been updated.');

        return Command::SUCCESS;
    }
}
