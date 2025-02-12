<?php

namespace App\Entity;

use App\Repository\RaffleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RaffleRepository::class)]
class Raffle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column(length: 255)]
    private string $status = 'active';

    #[ORM\Column]
    private ?int $created_by = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at;

    #[ORM\Column(nullable: true)]
    private ?int $winner_id = null;

    #[ORM\OneToMany(mappedBy: 'raffle', targetEntity: Participant::class, orphanRemoval: true)]
    private Collection $participants;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $creator_name = null;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->start_time;
    }

    public function setStartTime(\DateTimeInterface $start_time): self
    {
        $this->start_time = $start_time;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->end_time;
    }

    public function setEndTime(\DateTimeInterface $end_time): self
    {
        $this->end_time = $end_time;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->created_by;
    }

    public function setCreatedBy(int $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getWinnerId(): ?int
    {
        return $this->winner_id;
    }

    public function setWinnerId(?int $winner_id): self
    {
        $this->winner_id = $winner_id;
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setRaffle($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            if ($participant->getRaffle() === $this) {
                $participant->setRaffle(null);
            }
        }

        return $this;
    }

    public function getCreatorName(): ?string
    {
        return $this->creator_name;
    }

    public function setCreatorName(?string $creator_name): self
    {
        $this->creator_name = $creator_name;
        return $this;
    }
}
