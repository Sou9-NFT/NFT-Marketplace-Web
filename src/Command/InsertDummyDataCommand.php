<?php

namespace App\Command;

use App\Entity\Artwork;
use App\Entity\BetSession;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Raffle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InsertDummyDataCommand extends Command
{
    protected static $defaultName = 'app:insert-dummy-data';
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Insert dummy data into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setName('Admin User');
        $admin->setBalance(500);
        
        
        // Hash the password (123456)
        $hashedPassword = $this->passwordHasher->hashPassword($admin, '123456');
        $admin->setPassword($hashedPassword);
        
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin user created: admin@admin.com (password: 123456)');

        // Create Regular User
        $user = new User();
        $user->setEmail('user@user.com');
        $user->setName('Regular User');
        $user->setBalance(0);
        
        // Hash the password (123456)
        $hashedPassword = $this->passwordHasher->hashPassword($user, '123456');
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Regular user created: user@user.com (password: 123456)');

        // Create Seller User
        $seller = new User();
        $seller->setEmail('seller@artitechs.com');
        $seller->setRoles(['ROLE_SELLER', 'ROLE_USER']);
        $seller->setName('Seller User');
        $seller->setBalance(1000);
        
        // Hash the password (123456)
        $hashedPassword = $this->passwordHasher->hashPassword($seller, '123456');
        $seller->setPassword($hashedPassword);
        
        $this->entityManager->persist($seller);
        $this->entityManager->flush();

        $io->success('Seller user created: seller@artitechs.com (password: 123456)');

        // Create Author User
        $author = new User();
        $author->setEmail('author@artitechs.com');
        $author->setRoles(['ROLE_AUTHOR', 'ROLE_USER']);
        $author->setName('Author User');
        $author->setBalance(250);
        
        // Hash the password (123456)
        $hashedPassword = $this->passwordHasher->hashPassword($author, '123456');
        $author->setPassword($hashedPassword);
        
        $this->entityManager->persist($author);
        $this->entityManager->flush();

        $io->success('Author user created: author@artitechs.com (password: 123456)');

        // Insert Category
        $category = new Category();
        $category->setName('Example Category');
        $category->setType('image');
        $category->setDescription('This is an example category description.');
        $this->entityManager->persist($category);

        // Insert Artwork
        $artwork = new Artwork();
        $artwork->setTitle('Example Artwork');
        $artwork->setDescription('This is an example description for the artwork.');
        $artwork->setPrice(100);
        $artwork->setImageName('example.jpg');
        $artwork->setCreator($seller);
        $artwork->setCategory($category);
        $this->entityManager->persist($artwork);

        // Create a sample raffle
        $raffle = new Raffle();
        $raffle->setTitle('Sample Raffle');
        $raffle->setRaffleDescription('This is a sample raffle description for testing purposes.');
        $raffle->setCreatorName('Admin');
        $raffle->setCreator($admin);
        $raffle->setStartTime(new \DateTime());
        $raffle->setEndTime(new \DateTime('+7 days'));
        $raffle->setStatus('active');
        $raffle->setCreatedAt(new \DateTime());
        $raffle->setImage('example-raffle.jpg');
        
        $this->entityManager->persist($raffle);

        $this->entityManager->flush();

        $io->success('Dummy data has been inserted successfully.');

        return Command::SUCCESS;
    }
}
