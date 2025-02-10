<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'This category already exists')]
class Category
{
    private const MIME_TYPE_PATTERNS = [
        'image' => '/^image\//',
        'video' => '/^video\//',
        'audio' => '/^audio\//'
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

    #[ORM\Column(type: 'json')]
    #[Assert\NotBlank(message: 'Allowed MIME types are required')]
    #[Assert\Count(
        min: 1,
        minMessage: 'At least one MIME type must be specified'
    )]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Regex([
            'pattern' => '/^(image|video|audio)\/[\w\-\+\.]+$/',
            'message' => 'Invalid MIME type format. Must be like "image/jpeg", "video/mp4", etc.'
        ])
    ])]
    private array $allowedMimeTypes = [];

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Artwork::class)]
    private Collection $artworks;

    public function __construct()
    {
        $this->artworks = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateMimeTypes(ExecutionContextInterface $context): void
    {
        if (empty($this->type) || empty($this->allowedMimeTypes)) {
            return;
        }

        $pattern = self::MIME_TYPE_PATTERNS[$this->type] ?? null;
        if ($pattern === null) {
            return;
        }

        foreach ($this->allowedMimeTypes as $mimeType) {
            if (!preg_match($pattern, $mimeType)) {
                $context->buildViolation('MIME type "{{ mime_type }}" does not match the selected category type "{{ type }}"')
                    ->setParameters([
                        '{{ mime_type }}' => $mimeType,
                        '{{ type }}' => $this->type
                    ])
                    ->atPath('allowedMimeTypes')
                    ->addViolation();
            }
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
        return $this->allowedMimeTypes;
    }

    public function setAllowedMimeTypes(array $allowedMimeTypes): static
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
        return $this;
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
