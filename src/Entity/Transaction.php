<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transaction')]
#[ApiResource]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $type = null;

    #[ORM\Column(length: 20, options: ['default' => 'PENDING'])]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $referenceType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $referenceId = null;

    #[ORM\Column(length: 10, options: ['default' => 'TND'])]
    private ?string $currency = 'TND';

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getReferenceType(): ?string { return $this->referenceType; }
    public function setReferenceType(?string $referenceType): static { $this->referenceType = $referenceType; return $this; }

    public function getReferenceId(): ?int { return $this->referenceId; }
    public function setReferenceId(?int $referenceId): static { $this->referenceId = $referenceId; return $this; }

    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(string $currency): static { $this->currency = $currency; return $this; }
}
