<?php

namespace App\Repository;

use App\Entity\Vote;
use App\Entity\User;
use App\Entity\Blog;
use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    public function findUserVote(User $user, $target)
    {
        $queryBuilder = $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user);

        if ($target instanceof Blog) {
            $queryBuilder->andWhere('v.blog = :target')
                ->setParameter('target', $target);
        } elseif ($target instanceof Comment) {
            $queryBuilder->andWhere('v.comment = :target')
                ->setParameter('target', $target);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getVoteCount($target): array
    {
        $queryBuilder = $this->createQueryBuilder('v')
            ->select('SUM(CASE WHEN v.voteType = 1 THEN 1 ELSE 0 END) as likes')
            ->addSelect('SUM(CASE WHEN v.voteType = -1 THEN 1 ELSE 0 END) as dislikes');

        if ($target instanceof Blog) {
            $queryBuilder->where('v.blog = :target');
        } elseif ($target instanceof Comment) {
            $queryBuilder->where('v.comment = :target');
        }

        $queryBuilder->setParameter('target', $target);
        
        $result = $queryBuilder->getQuery()->getOneOrNullResult();
        
        return [
            'likes' => (int)($result['likes'] ?? 0),
            'dislikes' => (int)($result['dislikes'] ?? 0),
            'total' => (int)(($result['likes'] ?? 0) - ($result['dislikes'] ?? 0))
        ];
    }
}