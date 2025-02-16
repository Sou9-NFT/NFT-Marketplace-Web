<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:make-user-role',
    description: 'Assigns a specific role to a user',
)]
class MakeUserAdminCommand extends Command
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Role to assign (admin, seller, author)', 'admin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $roleOption = strtolower($input->getOption('role'));

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        // Map role option to actual role name
        $roleMap = [
            'admin' => 'ROLE_ADMIN',
            'seller' => 'ROLE_SELLER',
            'author' => 'ROLE_AUTHOR'
        ];

        if (!isset($roleMap[$roleOption])) {
            $io->error(sprintf('Invalid role "%s". Available roles: admin, seller, author', $roleOption));
            return Command::FAILURE;
        }

        $role = $roleMap[$roleOption];
        $roles = $user->getRoles();

        if (in_array($role, $roles)) {
            $io->warning(sprintf('User already has the role "%s"', $role));
            return Command::SUCCESS;
        }

        $roles[] = $role;
        $user->setRoles(array_unique($roles));

        $this->entityManager->flush();

        $io->success(sprintf('Role "%s" has been assigned to user "%s"', $role, $email));

        return Command::SUCCESS;
    }
}
