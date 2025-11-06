<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'houses')]
class House {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'integer')]
    private int $sleepingPlaces;

    #[ORM\Column(type: 'integer')]
    private int $distanceToSea;

    public function __construct(
        string $name = '',
        int $sleepingPlaces = 0,
        int $distanceToSea = 0,
    ) {
        $this->name = $name;
        $this->sleepingPlaces = $sleepingPlaces;
        $this->distanceToSea = $distanceToSea;
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

    public function getSleepingPlaces(): int {
        return $this->sleepingPlaces;
    }

    public function setSleepingPlaces(int $beds): self {
        $this->sleepingPlaces = $beds;
        return $this;
    }

    public function getDistanceToSea(): int {
        return $this->distanceToSea;
    }

    public function setDistanceToSea(int $distanceToSea): self {
        $this->distanceToSea = $distanceToSea;
        return $this;
    }
}
