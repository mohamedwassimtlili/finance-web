<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\InsuredAssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InsuredAssetRepository::class)]
#[ORM\Table(name: 'insured_asset')]
#[ApiResource]
class InsuredAsset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'insuredAssets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $declaredValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $approvedValue = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $manufactureDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $brand = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): static { $this->reference = $reference; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getDeclaredValue(): ?string { return $this->declaredValue; }
    public function setDeclaredValue(string $declaredValue): static { $this->declaredValue = $declaredValue; return $this; }

    public function getApprovedValue(): ?string { return $this->approvedValue; }
    public function setApprovedValue(?string $approvedValue): static { $this->approvedValue = $approvedValue; return $this; }

    public function getManufactureDate(): ?\DateTimeInterface { return $this->manufactureDate; }
    public function setManufactureDate(\DateTimeInterface $manufactureDate): static { $this->manufactureDate = $manufactureDate; return $this; }

    public function getBrand(): ?string { return $this->brand; }
    public function setBrand(?string $brand): static { $this->brand = $brand; return $this; }
}
