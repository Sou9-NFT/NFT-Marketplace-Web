<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Email cannot be blank')]
    #[Assert\Email(
        message: 'The email {{ value }} is not a valid email.',
        mode: 'strict'
    )]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: 'decimal', precision: 20, scale: 3)]
    #[Assert\PositiveOrZero(message: 'Balance cannot be negative')]
    private float $balance = 0.0;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Password cannot be blank')]
    #[Assert\Length(
        min: 6,
        max: 50,
        minMessage: 'Your password must be at least {{ limit }} characters long',
        maxMessage: 'Your password cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*\d).+$/',
        message: 'Password must contain at least one uppercase letter and one number'
    )]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 32, nullable: false)]
    #[Assert\NotBlank(message: 'Name cannot be blank')]
    #[Assert\Length(
        min: 2,
        max: 32,
        minMessage: 'Your name must be at least {{ limit }} characters long',
        maxMessage: 'Your name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z\s]+$/',
        message: 'Name can only contain letters and spaces'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: 'The profile picture must be a valid URL', groups: ['profile_picture_update'])]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 42, nullable: true)]
    #[Assert\Length(exactly: 42, exactMessage: 'Ethereum address must be exactly {{ limit }} characters')]
    #[Assert\Regex(
        pattern: '/^0x[a-fA-F0-9]{40}$/',
        message: 'Invalid Ethereum address format'
    )]
    private ?string $walletAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $githubUsername = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Raffle::class)]
    private Collection $createdRaffles;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Participant::class)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TopUpRequest::class)]
    private Collection $topUpRequests;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->createdRaffles = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->topUpRequests = new ArrayCollection();
        $this->roles = ['ROLE_USER']; // Assign ROLE_USER by default
        $this->plainPassword = null; // Initialize plainPassword
        $this->balance = 0.0; // Initialize balance
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

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

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getWalletAddress(): ?string
    {
        return $this->walletAddress;
    }

    public function setWalletAddress(?string $walletAddress): static
    {
        $this->walletAddress = $walletAddress;
        return $this;
    }

    public function getGithubUsername(): ?string
    {
        return $this->githubUsername;
    }

    public function setGithubUsername(?string $githubUsername): static
    {
        $this->githubUsername = $githubUsername;
        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * @return Collection<int, TopUpRequest>
     */
    public function getTopUpRequests(): Collection
    {
        return $this->topUpRequests;
    }

    public function addTopUpRequest(TopUpRequest $topUpRequest): self
    {
        if (!$this->topUpRequests->contains($topUpRequest)) {
            $this->topUpRequests->add($topUpRequest);
            $topUpRequest->setUser($this);
        }
        return $this;
    }

    public function removeTopUpRequest(TopUpRequest $topUpRequest): self
    {
        if ($this->topUpRequests->removeElement($topUpRequest)) {
            if ($topUpRequest->getUser() === $this) {
                $topUpRequest->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Raffle>
     */
    public function getCreatedRaffles(): Collection
    {
        return $this->createdRaffles;
    }

    public function addCreatedRaffle(Raffle $raffle): self
    {
        if (!$this->createdRaffles->contains($raffle)) {
            $this->createdRaffles->add($raffle);
            $raffle->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedRaffle(Raffle $raffle): self
    {
        if ($this->createdRaffles->removeElement($raffle)) {
            // set the owning side to null (unless already changed)
            if ($raffle->getCreator() === $this) {
                $raffle->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participant $participant): self
    {
        if (!$this->participations->contains($participant)) {
            $this->participations->add($participant);
            $participant->setUser($this);
        }

        return $this;
    }

    public function removeParticipation(Participant $participant): self
    {
        if ($this->participations->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getUser() === $this) {
                $participant->setUser(null);
            }
        }

        return $this;
    }
}
