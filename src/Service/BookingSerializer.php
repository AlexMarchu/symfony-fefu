<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;

class BookingSerializer
{
    public function serialize(Booking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'house_id' => $booking->getHouse()->getId(),
            'user_id' => $booking->getUser()->getId(),
            'user_phone' => $booking->getUser()->getPhone(),
            'user_name' => $booking->getUser()->getName(),
            'comment' => $booking->getComment(),
            'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s'),
            'status' => $booking->getStatus(),
        ];
    }

    public function serializeCollection(array $bookings): array
    {
        return array_map([$this, 'serialize'], $bookings);
    }
}
