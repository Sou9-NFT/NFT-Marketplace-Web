<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Raffle $raffle = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $joined_at;

    public function __construct()
    {
        $this->joined_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaffle(): ?Raffle
    {
        return $this->raffle;
    }

    public function setRaffle(?Raffle $raffle): self
    {
        $this->raffle = $raffle;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        if ($user) {
            $this->name = $user->getName() ?? $user->getEmail();
        }
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface 
    {
        return $this->joined_at;
    }

    public function setJoinedAt(\DateTimeInterface $joined_at): self
    {
        $this->joined_at = $joined_at;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?: $this->user?->getEmail() ?: 'Unknown Participant';
    }
}
