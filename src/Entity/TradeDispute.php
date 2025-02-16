<?php

namespace App\Entity;

use App\Repository\TradeDisputeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TradeDisputeRepository::class)]
class TradeDispute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

 

    #[ORM\ManyToOne(targetEntity: "App\Entity\TradeOffer")]
    #[ORM\JoinColumn(name: "trade_id", referencedColumnName: "id", nullable: false)]
    private ?TradeOffer $trade_id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $offered_item;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $received_item;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $evidence = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 255)]
    private ?string $status = 'pending';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTradeId(): ?TradeOffer
    {
        return $this->trade_id;
    }

    public function setTradeId(TradeOffer $trade_id): static
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

    public function setReason(string $reason): static
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
