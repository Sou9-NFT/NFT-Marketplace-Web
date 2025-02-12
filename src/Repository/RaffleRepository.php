<?php

namespace App\Repository;

use App\Entity\Raffle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RaffleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Raffle::class);
    }

    public function findActiveRaffles()
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('r.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
