<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Controller\UserController;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            controller: UserController::class . '::getAllUsers',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
        new Post(
            uriTemplate: '/users/create',
            controller: UserController::class . '::createUser',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
        new Delete(
            uriTemplate: '/users/{id}',
            controller: UserController::class . '::deleteUser',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 155)]
    private string $name;

    #[ORM\Column(type: 'string', length: 16, unique: true, nullable: false)]
    private string $phone;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    public function __construct(
        string $name = '',
        string $phone = '',
        array $roles = [self::ROLE_USER],
    ) {
        $this->name = $name;
        $this->phone = $phone;
        $this->roles = $roles;
        $this->createdAt = new \DateTime();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self
    {
        $this->bookings->removeElement($booking);

        return $this;
    }

    /**
     * Security methods
     */
    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_USER;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    #[Override]
    public function eraseCredentials(): void
    {
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        /** @var non-empty-string */
        return $this->phone;
    }

    public function isAdmin(): bool
    {
        return in_array(self::ROLE_ADMIN, $this->getRoles(), true);
    }

    public function promoteToAdmin(): self
    {
        $roles = $this->roles;
        if (!in_array(self::ROLE_ADMIN, $roles, true)) {
            $roles[] = self::ROLE_ADMIN;
            $this->setRoles($roles);
        }

        return $this;
    }

    public function demoteFromAdmin(): self
    {
        $roles = $this->roles;
        $key = array_search(self::ROLE_ADMIN, $roles, true);
        if (false !== $key) {
            unset($roles[$key]);
            $this->setRoles(array_values($roles));
        }

        return $this;
    }
}
