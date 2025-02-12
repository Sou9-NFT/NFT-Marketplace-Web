<?php
namespace App\Repository;


use App\Entity\BetSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;





/**
 * @extends ServiceEntityRepository<BetSession>
 *
 * @method BetSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method BetSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method BetSession[]    findAll()
 * @method BetSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BetSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetSession::class);
    }

    public function save(BetSession $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(BetSession $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // Add your custom repository methods below
}