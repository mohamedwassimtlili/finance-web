<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ContractRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractRequestRepository::class)]
#[ORM\Table(name: 'contract_request')]
#[ApiResource]
class ContractRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'contractRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: InsuredAsset::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?InsuredAsset $asset = null;

    #[ORM\ManyToOne(targetEntity: InsurancePackage::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?InsurancePackage $package = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $calculatedPremium = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $boldsignDocumentId = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAsset(): ?InsuredAsset { return $this->asset; }
    public function setAsset(?InsuredAsset $asset): static { $this->asset = $asset; return $this; }

    public function getPackage(): ?InsurancePackage { return $this->package; }
    public function setPackage(?InsurancePackage $package): static { $this->package = $package; return $this; }

    public function getCalculatedPremium(): ?string { return $this->calculatedPremium; }
    public function setCalculatedPremium(?string $calculatedPremium): static { $this->calculatedPremium = $calculatedPremium; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getBoldsignDocumentId(): ?string { return $this->boldsignDocumentId; }
    public function setBoldsignDocumentId(?string $boldsignDocumentId): static { $this->boldsignDocumentId = $boldsignDocumentId; return $this; }
}
