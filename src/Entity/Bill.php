<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BillRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: BillRepository::class)]
#[ORM\Table(name: 'bills')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class Bill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
#[Assert\NotBlank(message: 'Bill name is required')]
#[Assert\Length(min: 2, max: 100, minMessage: 'Name must be at least 2 characters')]
private ?string $name = null;

   #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
#[Assert\NotBlank(message: 'Amount is required')]
#[Assert\Positive(message: 'Amount must be greater than 0')]
#[Assert\LessThan(value: 1000000, message: 'Amount seems too high')]
private ?string $amount = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 31)]
    private ?int $dueDay = null;

    #[ORM\Column(length: 20)]
#[Assert\NotBlank(message: 'Frequency is required')]
#[Assert\Choice(choices: ['MONTHLY', 'WEEKLY', 'YEARLY'], message: 'Invalid frequency')]
private ?string $frequency = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Budget::class, inversedBy: 'bills')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Budget $budget = null;

    #[ORM\Column(length: 20, options: ['default' => 'UNPAID'])]
    private ?string $status = 'UNPAID';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;
        // Relationship with Expense
    #[ORM\OneToOne(mappedBy: 'bill', cascade: ['persist', 'remove'])]
    private ?Expense $expense = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getDueDay(): ?int { return $this->dueDay; }
    public function setDueDay(int $dueDay): static { $this->dueDay = $dueDay; return $this; }

    public function getFrequency(): ?string { return $this->frequency; }
    public function setFrequency(string $frequency): static { $this->frequency = $frequency; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getBudget(): ?Budget { return $this->budget; }
    public function setBudget(?Budget $budget): static { $this->budget = $budget; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
        public function getExpense(): ?Expense
    {
        return $this->expense;
    }

    public function setExpense(?Expense $expense): static
    {
        $this->expense = $expense;
        return $this;
    }

}
