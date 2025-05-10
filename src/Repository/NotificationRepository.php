<?php
namespace App\Repository;


use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 *
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Find notifications ordered by time (newest first)
     * 
     * @param int|null $limit Maximum number of notifications to retrieve
     * @return Notification[]
     */
    public function findRecentNotifications(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('n.time', 'DESC');
            
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Find notifications by type
     * 
     * @param string $type Notification type
     * @return Notification[]
     */
    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['time' => 'DESC']);
    }

    /**
     * Save a notification entity
     */
    public function save(Notification $notification, bool $flush = true): void
    {
        $this->getEntityManager()->persist($notification);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a notification entity
     */
    public function remove(Notification $notification, bool $flush = true): void
    {
        $this->getEntityManager()->remove($notification);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}