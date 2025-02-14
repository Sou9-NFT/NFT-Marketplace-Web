<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddBalanceCommand extends Command
{
    protected static $defaultName = 'app:add-balance';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add balance to a user account')
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('amount', InputArgument::REQUIRED, 'Amount to add');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $amount = (float) $input->getArgument('amount');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln(sprintf('<error>User with email %s not found!</error>', $email));
            return Command::FAILURE;
        }

        $currentBalance = $user->getBalance();
        $user->setBalance($currentBalance + $amount);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(sprintf(
            '<info>Successfully added %.2f to user %s. New balance: %.2f</info>',
            $amount,
            $email,
            $user->getBalance()
        ));

        return Command::SUCCESS;
    }
}
