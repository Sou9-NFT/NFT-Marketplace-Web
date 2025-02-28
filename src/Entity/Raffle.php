<?php

namespace App\Entity;

use App\Repository\RaffleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RaffleRepository::class)]
class Raffle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $start_time = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: "The end time cannot be blank.")]
    #[Assert\GreaterThan(propertyPath: "start_time", message: "The end time must be after the start time.")]
    #[Assert\GreaterThan(value: "today", message: "The end time must be in the future.")]
    private ?\DateTimeInterface $end_time = null;

    #[ORM\Column(length: 255)]
    private string $status = 'active';

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdRaffles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at;

    #[ORM\Column(nullable: true)]
    private ?int $winner_id = null;

    #[ORM\OneToMany(mappedBy: 'raffle', targetEntity: Participant::class, cascade: ['persist', 'remove'])]
    private Collection $participants;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "The creator name cannot be blank.")]
    #[Assert\Length(max: 255, maxMessage: "The creator name cannot be longer than {{ limit }} characters.")]
    private ?string $creator_name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The title cannot be blank.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "The title must be at least {{ limit }} characters long",
        maxMessage: "The title cannot be longer than {{ limit }} characters"
    )]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "The description cannot be blank.")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "The description must be at least {{ limit }} characters long",
        maxMessage: "The description cannot be longer than {{ limit }} characters"
    )]
    private ?string $raffleDescription = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->start_time = new \DateTime();
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

    public function setEndTime(?\DateTimeInterface $end_time): self
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

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;
        if ($creator) {
            $this->creator_name = $creator->getName() ?? $creator->getEmail();
        }
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

    /**
     * @return Collection<int, Participant>
     */
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
            // set the owning side to null (unless already changed)
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getRaffleDescription(): ?string
    {
        return $this->raffleDescription;
    }

    public function setRaffleDescription(string $raffleDescription): self
    {
        $this->raffleDescription = $raffleDescription;
        return $this;
    }
}