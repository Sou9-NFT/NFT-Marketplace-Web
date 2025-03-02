<?php

namespace App\Entity;

use App\Repository\BetSessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BetSessionRepository::class)]
class BetSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: Artwork::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: "Artwork cannot be blank.")]
    private ?Artwork $artwork = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: false)]
    #[Assert\GreaterThan(propertyPath: "startTime", message: "End time must be after the start time.")]
    #[Assert\NotBlank(message: "End time cannot be blank.")]
    private ?\DateTimeImmutable $endTime = null;

  

    #[ORM\Column(nullable: false)]
    #[Assert\GreaterThan("today", message: "Start time must be in the future.")]
    #[Assert\NotBlank(message: "Start time cannot be blank.")]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Initial price cannot be blank.")]
    private ?float $initialPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?float $currentPrice = null;

    #[ORM\Column(length: 10)]
    private string $status = 'pending';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->currentPrice = $this->initialPrice;
        $this->updateStatus();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getArtwork(): ?Artwork
    {
        return $this->artwork;
    }

    public function setArtwork(Artwork $artwork): static
    {
        $this->artwork = $artwork;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;
        $this->updateStatus();
        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getInitialPrice(): ?float
    {
        return $this->initialPrice;
    }

    public function setInitialPrice(?float $initialPrice): static
    {
        $this->initialPrice = $initialPrice;
        return $this;
    }

    public function getCurrentPrice(): ?float
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?float $currentPrice): static
    {
        $this->currentPrice = $currentPrice;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
    public function updateStatus(): void
    {
        if ($this->endTime !== null && $this->endTime < new \DateTimeImmutable()) {
            $this->status = 'ended';
        } elseif ($this->startTime !== null && $this->startTime > new \DateTimeImmutable()) {
            $this->status = 'pending';
        } else {
            $this->status = 'active';
        }
    }
}