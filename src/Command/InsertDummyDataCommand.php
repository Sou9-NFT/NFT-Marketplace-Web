<?php

namespace App\Command;

use App\Entity\Artwork;
use App\Entity\BetSession;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InsertDummyDataCommand extends Command
{
    protected static $defaultName = 'app:insert-dummy-data';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Insert dummy data into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Insert Category
        $category = new Category();
        $category->setName('Example Category');
        $category->setType('image');
        $category->setDescription('This is an example category description.');
        $category->setAllowedMimeTypes(['image/jpeg', 'image/png']);
        $this->entityManager->persist($category);

        // Create Admin User
        // password: 123456
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword('$2y$13$r98NpeevOc747TJJk0VQce/EqIWvUKv1nNiONsyL3xsEWeN33DEq2');
        $admin->setName('Admin User');
        $admin->setBalance(500);
        $admin->setCreatedAt(new \DateTimeImmutable('2025-02-12 18:59:48'));
        $this->entityManager->persist($admin);

        // Create Regular User
        // password: 123456
        $user = new User();
        $user->setEmail('user@user.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('$2y$13$r98NpeevOc747TJJk0VQce/EqIWvUKv1nNiONsyL3xsEWeN33DEq2');
        $user->setName('Regular User');
        $user->setBalance(0);
        $user->setCreatedAt(new \DateTimeImmutable('2025-02-12 18:59:48'));
        $this->entityManager->persist($user);

        // Create Seller User
        // password: 123456
        $seller = new User();
        $seller->setEmail('seller@artitechs.com');
        $seller->setRoles(['ROLE_SELLER', 'ROLE_USER']);
        $seller->setPassword('$2y$13$r98NpeevOc747TJJk0VQce/EqIWvUKv1nNiONsyL3xsEWeN33DEq2');
        $seller->setName('Seller User');
        $seller->setBalance(1000);
        $seller->setCreatedAt(new \DateTimeImmutable('2025-02-12 18:59:48'));
        $this->entityManager->persist($seller);

        // Create Author User
        // password: 123456
        $author = new User();
        $author->setEmail('author@artitechs.com');
        $author->setRoles(['ROLE_AUTHOR', 'ROLE_USER']);
        $author->setPassword('$2y$13$r98NpeevOc747TJJk0VQce/EqIWvUKv1nNiONsyL3xsEWeN33DEq2');
        $author->setName('Author User');
        $author->setBalance(250);
        $author->setCreatedAt(new \DateTimeImmutable('2025-02-12 18:59:48'));
        $this->entityManager->persist($author);

        // Insert Artwork
        $artwork = new Artwork();
        $artwork->setCategory($category);
        $artwork->setTitle('Example Artwork');
        $artwork->setDescription('This is an example description for the artwork.');
        $artwork->setPrice(100);
        $artwork->setImageName('example.jpg');
        $artwork->setUpdatedAt(new \DateTimeImmutable('2025-02-12 10:00:00'));
        $artwork->setCreatedAt(new \DateTimeImmutable('2025-02-12 10:00:00'));
        $this->entityManager->persist($artwork);

        // Insert BetSession
        for ($i = 2; $i <= 9; $i++) {
            $betSession = new BetSession();
            $betSession->setAuthor($seller); // Using seller as the author for bet sessions
            $betSession->setArtwork($artwork);
            $betSession->setCreatedAt(new \DateTimeImmutable('2025-02-12 10:00:00'));
            $betSession->setEndTime(new \DateTimeImmutable('2025-02-15 10:00:00'));
            $betSession->setStartTime(new \DateTimeImmutable('2025-02-13 10:00:00'));
            $betSession->setInitialPrice(100.00 * $i);
            $betSession->setCurrentPrice(100.00 * $i);
            $this->entityManager->persist($betSession);
        }

        $this->entityManager->flush();

        $io->success('Dummy data has been inserted successfully.');

        return Command::SUCCESS;
    }
}
