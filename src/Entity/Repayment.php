<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RepaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RepaymentRepository::class)]
#[ORM\Table(name: 'repayment')]
#[ApiResource]
class Repayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Loan::class, inversedBy: 'repayments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Loan $loan = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $paymentDate = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentType = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $monthlyPayment = null;

    public function getId(): ?int { return $this->id; }

    public function getLoan(): ?Loan { return $this->loan; }
    public function setLoan(?Loan $loan): static { $this->loan = $loan; return $this; }

    public function getAmount(): ?string { return $this->amount; }
    public function setAmount(string $amount): static { $this->amount = $amount; return $this; }

    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    public function setPaymentDate(\DateTimeInterface $paymentDate): static { $this->paymentDate = $paymentDate; return $this; }

    public function getPaymentType(): ?string { return $this->paymentType; }
    public function setPaymentType(?string $paymentType): static { $this->paymentType = $paymentType; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getMonthlyPayment(): ?string { return $this->monthlyPayment; }
    public function setMonthlyPayment(?string $monthlyPayment): static { $this->monthlyPayment = $monthlyPayment; return $this; }
}
