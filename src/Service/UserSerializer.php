<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class UserSerializer
{
    public function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'phone' => $user->getPhone(),
            'created_at' => $user->getCreatedAt(),
        ];
    }

    public function serializeCollection(array $users): array
    {
        return array_map([$this, 'serialize'], $users);
    }
}
