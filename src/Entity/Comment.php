<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Author name cannot be empty')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Author name must be at least {{ limit }} characters long',
        maxMessage: 'Author name cannot exceed {{ limit }} characters'
    )]
    private ?string $author = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Comment cannot be empty')]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: 'Comment must be at least {{ limit }} characters long',
        maxMessage: 'Comment cannot exceed {{ limit }} characters'
    )]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Blog::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Blog $blog = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBlog(): ?Blog
    {
        return $this->blog;
    }

    public function setBlog(?Blog $Blog): static
    {
        $this->blog = $Blog;

        return $this;
    }
}
