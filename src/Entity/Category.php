<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'This category already exists')]
#[ORM\HasLifecycleCallbacks]
class Category
{
    private const DEFAULT_MIME_TYPES = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg'
        ],
        'audio' => [
            'audio/mpeg',
            'audio/ogg',
            'audio/wav',
            'audio/webm'
        ]
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Category name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Category name must be at least {{ limit }} characters long',
        maxMessage: 'Category name cannot be longer than {{ limit }} characters'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'File type is required')]
    #[Assert\Choice(
        choices: ['image', 'video', 'audio'],
        message: 'Choose a valid file type: image, video, or audio'
    )]
    private ?string $type = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'Description must be at least {{ limit }} characters long',
        maxMessage: 'Description cannot be longer than {{ limit }} characters'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $allowedMimeTypes = [];

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Artwork::class)]
    private Collection $artworks;

    public function __construct()
    {
        $this->artworks = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateAllowedMimeTypes(): void
    {
        if ($this->type) {
            $this->allowedMimeTypes = self::DEFAULT_MIME_TYPES[$this->type];
        }
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        $this->updateAllowedMimeTypes();
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

    public function getAllowedMimeTypes(): array
    {
        if (!isset($this->allowedMimeTypes) || empty($this->allowedMimeTypes)) {
            return $this->type ? self::DEFAULT_MIME_TYPES[$this->type] : [];
        }
        return $this->allowedMimeTypes;
    }

    /**
     * @return Collection<int, Artwork>
     */
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

    public function __toString(): string
    {
        return $this->name;
    }
}
