<?php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "`user`")]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ["groups" => ["user:read"]],
    denormalizationContext: ["groups" => ["user:write"]],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Regex(pattern: '/^[a-zA-Z\s]+$/', message: 'Name can only contain letters and spaces.')]
    private ?string $name = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(["user:write"])]
    private ?string $passwordHash = null;

    #[ORM\Column(type: Types::INTEGER, options: ["default" => 2])]
    #[Groups(["user:read", "user:write"])]
    private ?int $roleId = 2;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private bool $isVerified = false;

    #[ORM\Column(length: 30, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Regex(pattern: '/^[0-9]{8}$/', message: 'Phone must be exactly 8 digits.')]
    private ?string $phone = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(["user:write"])]
    private ?string $verificationCode = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private ?bool $googleAccount = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 0])]
    #[Groups(["user:read", "user:write"])]
    private bool $faceRegistered = false;
    #[ORM\Column(type: Types::BOOLEAN, options: ["default" => 1])]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Budget::class)]
    private Collection $budgets;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Complaint::class)]
    private Collection $complaints;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: InsuredAsset::class)]
    private Collection $insuredAssets;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Loan::class)]
    private Collection $loans;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Transaction::class)]
    private Collection $transactions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ContractRequest::class)]
    private Collection $contractRequests;

    public function __construct()
    {
        $this->budgets = new ArrayCollection();
        $this->complaints = new ArrayCollection();
        $this->insuredAssets = new ArrayCollection();
        $this->loans = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->contractRequests = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = ["ROLE_USER"];

        if ($this->roleId === 1) {
            $roles[] = "ROLE_ADMIN";
        }

        return array_unique($roles);
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function setRoleId(int $roleId): static
    {
        $this->roleId = $roleId;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPassword(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function setPasswordHash(string $passwordHash): static
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verificationCode;
    }

    public function setVerificationCode(?string $verificationCode): static
    {
        $this->verificationCode = $verificationCode;
        return $this;
    }

    public function getGoogleAccount(): ?bool
    {
        return $this->googleAccount;
    }

    public function setGoogleAccount(?bool $googleAccount): static
    {
        $this->googleAccount = $googleAccount;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function isFaceRegistered(): bool
    {
        return $this->faceRegistered;
    }

    public function setFaceRegistered(bool $faceRegistered): static
    {
        $this->faceRegistered = $faceRegistered;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getBudgets(): Collection { return $this->budgets; }
    public function getComplaints(): Collection { return $this->complaints; }
    public function getInsuredAssets(): Collection { return $this->insuredAssets; }
    public function getLoans(): Collection { return $this->loans; }
    public function getTransactions(): Collection { return $this->transactions; }
    public function getContractRequests(): Collection { return $this->contractRequests; }
}

