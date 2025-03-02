<?php

namespace App\Entity;

use App\Repository\TradeStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\TradeOffer; // Import TradeOffer entity
use App\Entity\User; // Import User entity
use App\Entity\Artwork; // Import Artwork entity

#[ORM\Entity(repositoryClass: TradeStateRepository::class)]
class TradeState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TradeOffer::class)]
    #[ORM\JoinColumn(name: 'trade_offer_id', referencedColumnName: 'id', nullable: false, onDelete: "CASCADE")]
    private ?TradeOffer $tradeOffer = null;

    #[ORM\ManyToOne(targetEntity: Artwork::class)]
    #[ORM\JoinColumn(name: 'received_item', referencedColumnName: 'id', nullable: false)]
    private ?Artwork $received_item = null;

    #[ORM\ManyToOne(targetEntity: Artwork::class)]
    #[ORM\JoinColumn(name: 'offered_item', referencedColumnName: 'id', nullable: false)]
    private ?Artwork $offered_item = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'receiver_id', referencedColumnName: 'id', nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTradeOffer(): ?TradeOffer
    {
        return $this->tradeOffer;
    }

    public function setTradeOffer(?TradeOffer $tradeOffer): static
    {
        $this->tradeOffer = $tradeOffer;
        return $this;
    }

    public function getReceivedItem(): ?Artwork
    {
        return $this->received_item;
    }

    public function setReceivedItem(?Artwork $received_item): static
    {
        $this->received_item = $received_item;
        return $this;
    }

    public function getOfferedItem(): ?Artwork
    {
        return $this->offered_item;
    }

    public function setOfferedItem(?Artwork $offered_item): static
    {
        $this->offered_item = $offered_item;
        return $this;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): static
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): static
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
