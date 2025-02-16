<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(
    fields: ['name'],
    message: 'A category with this name already exists'
)]
class Category
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_AUDIO = 'audio';

    public const MIME_TYPES = [
        self::TYPE_IMAGE => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        self::TYPE_VIDEO => [
            'video/mp4',
            'video/webm',
            'video/ogg'
        ],
        self::TYPE_AUDIO => [
            'audio/mpeg',
            'audio/ogg',
            'audio/wav'
        ]
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Name cannot be empty')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters long',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Type cannot be empty')]
    #[Assert\Choice(choices: [self::TYPE_IMAGE, self::TYPE_VIDEO, self::TYPE_AUDIO], message: 'Invalid category type')]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'Description cannot be empty')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Description must be at least {{ limit }} characters long'
    )]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Artwork::class)]
    private Collection $artworks;

    #[ORM\Column(type: Types::JSON)]
    private array $allowedMimeTypes = [];

    public function __construct()
    {
        $this->artworks = new ArrayCollection();
        $this->allowedMimeTypes = [];
    }

    #[Assert\Callback]
    public function validateMimeTypes(ExecutionContextInterface $context): void
    {
        if (!$this->type) {
            return;
        }

        if (!isset(self::MIME_TYPES[$this->type])) {
            $context->buildViolation('Invalid category type')
                ->atPath('type')
                ->addViolation();
            return;
        }

        if ($this->allowedMimeTypes !== self::MIME_TYPES[$this->type]) {
            $this->allowedMimeTypes = self::MIME_TYPES[$this->type];
        }
    }

    public static function getAvailableMimeTypes(?string $type = null): array
    {
        if ($type === null) {
            return self::MIME_TYPES;
        }
        return self::MIME_TYPES[$type] ?? [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_IMAGE, self::TYPE_VIDEO, self::TYPE_AUDIO])) {
            throw new \InvalidArgumentException('Invalid category type');
        }

        $this->type = $type;
        $this->allowedMimeTypes = self::MIME_TYPES[$type];

        return $this;
    }

    public function getArtworks(): Collection
    {
        return $this->artworks;
    }

    public function addArtwork(Artwork $artwork): static
    {
        if (!$this->artworks->contains($artwork)) {
            $this->artworks->add($artwork);
            $artwork->setCategory($this);
        }
        return $this;
    }

    public function removeArtwork(Artwork $artwork): static
    {
        if ($this->artworks->removeElement($artwork)) {
            if ($artwork->getCategory() === $this) {
                $artwork->setCategory(null);
            }
        }
        return $this;
    }

    public function getAllowedMimeTypes(): array
    {
        if (empty($this->allowedMimeTypes) && $this->type) {
            return self::MIME_TYPES[$this->type] ?? [];
        }
        return $this->allowedMimeTypes;
    }

    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
