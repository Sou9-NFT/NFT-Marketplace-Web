<?php

namespace App\Entity;

use App\Repository\BidRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BidRepository::class)]
class Bid
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    #[ORM\Column(type: 'integer')]
    private ?int $bidValue = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $bidTime = null;

    #[ORM\ManyToOne(targetEntity: BetSession::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?BetSession $betSession = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBidValue(): ?int
    {
        return $this->bidValue;
    }

    public function setBidValue(int $bidValue): static
    {
        $this->bidValue = $bidValue;
        return $this;
    }

    public function getBidTime(): ?\DateTimeImmutable
    {
        return $this->bidTime;
    }

    public function setBidTime(\DateTimeImmutable $bidTime): static
    {
        $this->bidTime = $bidTime;
        return $this;
    }

    public function getBetSession(): ?BetSession
    {
        return $this->betSession;
    }

    public function setBetSession(BetSession $betSession): static
    {
        $this->betSession = $betSession;
        return $this;
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
}
