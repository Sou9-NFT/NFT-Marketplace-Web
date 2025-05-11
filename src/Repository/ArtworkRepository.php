<?php

namespace App\Repository;

use App\Entity\Artwork;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artwork>
 *
 * @method Artwork|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artwork|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artwork[]    findAll()
 * @method Artwork[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtworkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artwork::class);
    }

    public function save(Artwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Artwork $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchByTerm(?string $searchTerm = null, ?string $sortBy = null, ?string $direction = 'ASC')
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.category', 'c')
            ->leftJoin('a.creator', 'u');

        if ($searchTerm) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('a.title', ':searchTerm'),
                $qb->expr()->like('a.description', ':searchTerm'),
                $qb->expr()->like('c.name', ':searchTerm')
            ))
            ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Default sort
        if (!$sortBy) {
            $sortBy = 'date';
        }

        // Ensure valid direction
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        switch ($sortBy) {
            case 'price':
                $qb->orderBy('a.price', $direction);
                break;
            case 'date':
                $qb->orderBy('a.createdAt', $direction);
                break;
            case 'title':
                $qb->orderBy('a.title', $direction);
                break;
            case 'category':
                $qb->orderBy('c.name', $direction);
                break;
            default:
                $qb->orderBy('a.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find artworks by category
     */
    public function findByCategory(Category $category)
    {
        return $this->createQueryBuilder('a')
            ->where('a.category = :category')
            ->setParameter('category', $category)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Search artworks by title or description
     */
    public function search(string $query)
    {
        return $this->createQueryBuilder('a')
            ->where('a.title LIKE :query OR a.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find artworks by owner
     */
    public function findByOwner($owner)
    {
        return $this->createQueryBuilder('a')
            ->where('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
