<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Entity\Vote;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/vote')]
class VoteController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VoteRepository $voteRepository
    ) {}

    #[Route('/blog/{id}/{type}', name: 'app_vote_blog', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function voteBlog(Blog $blog, string $type): JsonResponse
    {
        return $this->handleVote($blog, $type);
    }

    #[Route('/comment/{id}/{type}', name: 'app_vote_comment', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function voteComment(Comment $comment, string $type): JsonResponse
    {
        return $this->handleVote($comment, $type);
    }

    #[Route('/blog/{id}/counts', name: 'app_vote_blog_counts', methods: ['GET'])]
    public function getBlogVoteCounts(Blog $blog): JsonResponse
    {
        return new JsonResponse($this->voteRepository->getVoteCount($blog));
    }

    #[Route('/comment/{id}/counts', name: 'app_vote_comment_counts', methods: ['GET'])]
    public function getCommentVoteCounts(Comment $comment): JsonResponse
    {
        return new JsonResponse($this->voteRepository->getVoteCount($comment));
    }

    private function handleVote($target, string $type): JsonResponse
    {
        if (!in_array($type, ['like', 'dislike'])) {
            return new JsonResponse(['error' => 'Invalid vote type'], 400);
        }

        $user = $this->getUser();
        $voteType = $type === 'like' ? 1 : -1;

        $existingVote = $this->voteRepository->findUserVote($user, $target);

        if ($existingVote) {
            if ($existingVote->getVoteType() === $voteType) {
                // Remove vote if clicking the same button
                $this->entityManager->remove($existingVote);
            } else {
                // Change vote type if clicking different button
                $existingVote->setVoteType($voteType);
            }
        } else {
            // Create new vote
            $vote = new Vote();
            $vote->setUser($user);
            $vote->setVoteType($voteType);
            
            if ($target instanceof Blog) {
                $vote->setBlog($target);
            } else {
                $vote->setComment($target);
            }
            
            $this->entityManager->persist($vote);
        }

        $this->entityManager->flush();

        // Get updated vote counts
        $counts = $this->voteRepository->getVoteCount($target);
        
        return new JsonResponse($counts);
    }
}