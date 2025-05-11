<?php

namespace App\Repository;

use App\Entity\Blog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blog>
 */
class BlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blog::class);
    }

    public function getBlogAnalytics(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Total posts
        $totalPosts = $this->count([]);
        
        // Posts per month for the last 12 months
        $monthlyStats = $conn->executeQuery(
            'SELECT DATE_FORMAT(date, "%Y-%m") as month, COUNT(*) as count 
             FROM blog 
             WHERE date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(date, "%Y-%m")
             ORDER BY month DESC'
        )->fetchAllAssociative();
        
        // Most commented posts
        $mostCommented = $conn->executeQuery(
            'SELECT b.id, b.title, COUNT(c.id) as comment_count 
             FROM blog b 
             LEFT JOIN comment c ON b.id = c.blog_id 
             GROUP BY b.id 
             ORDER BY comment_count DESC 
             LIMIT 5'
        )->fetchAllAssociative();

        return [
            'total_posts' => $totalPosts,
            'monthly_stats' => $monthlyStats,
            'most_commented' => $mostCommented,
        ];
    }
}
