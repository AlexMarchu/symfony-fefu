<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\BookingController;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bookings')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/bookings',
            controller: BookingController::class . '::getAllBookings',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
        new Post(
            uriTemplate: '/bookings/create',
            controller: BookingController::class . '::createBooking',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
        new Put(
            uriTemplate: '/booking/{id}/comment',
            controller: BookingController::class . '::updateBookingComment',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
        new Delete(
            uriTemplate: '/bookings/{id}',
            controller: BookingController::class . '::deleteBooking',
            security: 'is_granted("IS_AUTHENTICATED_FULLY")'
        ),
    ]
)]
class Booking
{
    private const STATUS_ACTIVE = 'active';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: House::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private House $house;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status = self::STATUS_ACTIVE;

    public function __construct(
        House $house,
        User $user,
        string $comment = '',
    ) {
        $this->house = $house;
        $this->user = $user;
        $this->comment = $comment;
        $this->createdAt = new \DateTime();

        $user->addBooking($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHouse(): House
    {
        return $this->house;
    }

    public function setHouse(House $house): self
    {
        $this->house = $house;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
