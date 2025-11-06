<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 155)]
    private string $name;

    #[ORM\Column(type: 'string', length: 16, unique: true, nullable: false)]
    private string $phone;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Booking::class, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    public function __construct(
        string $name = '',
        string $phone = '',
    ) {
        $this->name = $name;
        $this->phone = $phone;
        $this->createdAt = new \DateTime();
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function getPhone(): string {
        return $this->phone;
    }

    public function setPhone(string $phone): self {
        $this->phone = $phone;
        return $this;
    }

    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }

    public function getBookings(): Collection {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): self {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): self {
        $this->bookings->removeElement($booking);
        return $this;
    }
}