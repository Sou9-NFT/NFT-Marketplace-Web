<?php
namespace App\Entity;

  
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne; // Import ManyToOne
use Doctrine\ORM\Mapping\JoinColumn;
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ManyToOne(targetEntity: TradeState::class, cascade: ['remove'])]
    #[JoinColumn(nullable: true)]
    private ?TradeState $tradeState = null;
public function getTradeStateId(): ?int
{
    return $this->tradeState ? $this->tradeState->getId() : null;
}


public function setTradeState(?TradeState $tradeState): self
{
    $this->tradeState = $tradeState;
    return $this;
}
    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "receiver_id", referencedColumnName: "id")]
    private ?User $receiver = null;

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    #[ORM\Column]
    private ?\DateTimeImmutable $time = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;


    public function __construct()
    {
        $this->time = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeImmutable
    {
        return $this->time;
    }

    public function setTime(\DateTimeImmutable $time): self
    {
        $this->time = $time;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }


}