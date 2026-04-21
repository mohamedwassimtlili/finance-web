<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BudgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
#[ORM\Table(name: 'budget')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Budget
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
#[Assert\NotBlank(message: 'Budget name is required')]
#[Assert\Length(min: 3, max: 150, minMessage: 'Name must be at least 3 characters')]
private ?string $name = null;

#[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
#[Assert\NotBlank(message: 'Amount is required')]
#[Assert\Positive(message: 'Amount must be greater than 0')]
private ?string $amount = null;

#[ORM\Column(type: Types::DATE_MUTABLE)]
#[Assert\NotNull(message: 'Start date is required')]
private ?\DateTimeInterface $startDate = null;

#[ORM\Column(type: Types::DATE_MUTABLE)]
#[Assert\NotNull(message: 'End date is required')]
#[Assert\GreaterThan(propertyPath: 'startDate', message: 'End date must be after start date')]
private ?\DateTimeInterface $endDate = null;

    

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'budgets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['default' => '0.00'])]
    private ?string $spentAmount = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: Bill::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $bills;

    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: Expense::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $expenses;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function __construct()
    {
        $this->bills = new ArrayCollection();
        $this->expenses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): static { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(\DateTimeInterface $endDate): static { $this->endDate = $endDate; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getSpentAmount(): ?string { return $this->spentAmount; }
    public function setSpentAmount(string $spentAmount): static { $this->spentAmount = $spentAmount; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getBills(): Collection { return $this->bills; }
    public function getExpenses(): Collection { return $this->expenses; }
    
    public function getStatus(): string
{
    if ((float)$this->amount == 0) return 'No Budget';
    $percentage = ((float)$this->spentAmount / (float)$this->amount) * 100;
    if ($percentage <= 50) return 'On Track';
    if ($percentage <= 75) return 'Warning';
    if ($percentage <= 90) return 'Near Limit';
    return 'Overspent';
}
}
