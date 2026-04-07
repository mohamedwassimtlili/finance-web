<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\InsurancePackageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InsurancePackageRepository::class)]
#[ORM\Table(name: 'insurance_package')]
#[ApiResource]
class InsurancePackage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $assetType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverageDetails = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $basePrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '1.00'])]
    private ?string $riskMultiplier = '1.00';

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $durationMonths = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 1])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAssetType(): ?string { return $this->assetType; }
    public function setAssetType(string $assetType): static { $this->assetType = $assetType; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCoverageDetails(): ?string { return $this->coverageDetails; }
    public function setCoverageDetails(?string $coverageDetails): static { $this->coverageDetails = $coverageDetails; return $this; }

    public function getBasePrice(): ?string { return $this->basePrice; }
    public function setBasePrice(string $basePrice): static { $this->basePrice = $basePrice; return $this; }

    public function getRiskMultiplier(): ?string { return $this->riskMultiplier; }
    public function setRiskMultiplier(string $riskMultiplier): static { $this->riskMultiplier = $riskMultiplier; return $this; }

    public function getDurationMonths(): ?int { return $this->durationMonths; }
    public function setDurationMonths(int $durationMonths): static { $this->durationMonths = $durationMonths; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
