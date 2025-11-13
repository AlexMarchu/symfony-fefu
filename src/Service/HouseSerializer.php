<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\House;

class HouseSerializer
{
    public function serialize(House $house): array
    {
        return [
            'id' => $house->getId(),
            'name' => $house->getName(),
            'sleeping_places' => $house->getSleepingPlaces(),
            'distance_to_sea' => $house->getDistanceToSea(),
        ];
    }

    public function serializeCollection(array $houses): array
    {
        return array_map([$this, 'serialize'], $houses);
    }
}
