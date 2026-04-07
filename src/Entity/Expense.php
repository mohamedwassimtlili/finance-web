<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[ORM\Table(name: 'expense')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $expenseDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Budget::class, inversedBy: 'expenses')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Budget $budget = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getExpenseDate(): ?\DateTimeInterface { return $this->expenseDate; }
    public function setExpenseDate(\DateTimeInterface $expenseDate): static { $this->expenseDate = $expenseDate; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getBudget(): ?Budget { return $this->budget; }
    public function setBudget(?Budget $budget): static { $this->budget = $budget; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
