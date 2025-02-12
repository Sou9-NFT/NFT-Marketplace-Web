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

        // Insert User
        $user = new User();
        $user->setEmail('nessimbns2@gmail.com');
        $user->setRoles([]);
        $user->setPassword('$2y$13$r98NpeevOc747TJJk0VQce/EqIWvUKv1nNiONsyL3xsEWeN33DEq2'); // hashed password
        $user->setName('John Doe');
        $user->setCreatedAt(new \DateTimeImmutable('2025-02-12 18:59:48'));
        $this->entityManager->persist($user);

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
            $betSession->setAuthor($user);
            $betSession->setArtwork($artwork);
            $betSession->setCreatedAt(new \DateTimeImmutable('2025-02-12 10:00:00'));
            $betSession->setEndTime(new \DateTimeImmutable('2025-02-15 10:00:00'));
            $betSession->setStartTime(new \DateTimeImmutable('2025-02-13 10:00:00'));
            $betSession->setInitialPrice(100.00 * $i);
            $betSession->setCurrentPrice(100.00 * $i);
            $betSession->setStatus('pending');
            $this->entityManager->persist($betSession);
        }

        $this->entityManager->flush();

        $io->success('Dummy data has been inserted successfully.');

        return Command::SUCCESS;
    }
}