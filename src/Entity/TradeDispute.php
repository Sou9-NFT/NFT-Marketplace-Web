<?php

namespace App\Entity;

use App\Repository\TradeDisputeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TradeDisputeRepository::class)]
class TradeDispute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "reporter", referencedColumnName: "id", nullable: false)]
    #[Assert\NotNull(message: 'Reporter cannot be null')]
    private ?User $reporter = null;

    #[ORM\ManyToOne(targetEntity: TradeOffer::class)]
    #[ORM\JoinColumn(name: "trade_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    #[Assert\NotNull(message: 'Please select a trade offer')]
    private ?TradeOffer $trade_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $offered_item;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $received_item;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please provide a reason for the dispute')]
    private ?string $reason = null;

    #[ORM\Column(length: 255)]
    private ?string $evidence = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 255)]
    private ?string $status = 'pending';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;
        return $this;
    }

    public function getTradeId(): ?TradeOffer
    {
        return $this->trade_id;
    }

    public function setTradeId(?TradeOffer $trade_id): static
    {
        $this->trade_id = $trade_id;

        return $this;
    }

    public function getOfferedItem(): ?string
    {
        return $this->offered_item;
    }

    public function setOfferedItem(string $offered_item): static
    {
        $this->offered_item = $offered_item;

        return $this;
    }

    public function getReceivedItem(): ?string
    {
        return $this->received_item;
    }

    public function setReceivedItem(?string $received_item): static
    {
        $this->received_item = $received_item;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getEvidence(): ?string
    {
        return $this->evidence;
    }

    public function setEvidence(?string $evidence): static
    {
        $this->evidence = $evidence;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
