<?php

namespace App\Repository;

use App\Service\CSVService;
use Symfony\Component\HttpFoundation\Response;

class BookingRepository {

    private const HOUSES_CSV = 'houses.csv';
    private const BOOKINGS_CSV = 'bookings.csv';
    private const STATUS_ACTIVE = 'active';

    public function __construct(private CSVService $csvService) {
        $this->csvService = $csvService;
    }

    public function findAllHouses(): array {
        return $this->csvService->readCSV(self::HOUSES_CSV);
    }

    public function findAllAvailableHouses(): array {
        $houses = $this->csvService->readCSV(self::HOUSES_CSV);

        $data = [];
        foreach($houses as $house) {
            if (!$house['is_available']) 
                continue;
            $data[] = $house;
        }

        return $data;
    }

    public function findHouseById($houseId): ?array {
        $houses = $this->csvService->readCSV(self::HOUSES_CSV);

        foreach ($houses as $house) {
            if ($house['id'] == $houseId)
                return $house;
        }

        return null;
    }

    public function updateHouse(array $house): void {
        $houses = $this->findAllHouses();

        foreach ($houses as &$h) {
            if ($h['id'] == $house['id']) {
                $h = $house;
                break;
            }
        }

        $this->csvService->writeCSV(self::HOUSES_CSV, $houses);
    }

    public function findAllBookings(): array {
        return $this->csvService->readCSV(self::BOOKINGS_CSV);
    }

    public function findBookingById($bookingId): ?array {
        $bookings = $this->findAllBookings();

        foreach ($bookings as $booking)
            if ($booking['id'] == $bookingId)
                return $booking;

        return null;
    }

    public function createBooking(array $bookingData): array {
        $bookings = $this->findAllBookings();
        
        $newId = 1;
        if (!empty($bookings)) {
            $ids = array_column($bookings, 'id');
            $newId = max($ids) + 1;
        }
        
        $newBooking = [
            'id' => $newId,
            'house_id' => $bookingData['house_id'],
            'phone' => $bookingData['phone'],
            'comment' => $bookingData['comment'],
            'created_at' => date('Y-m-d H:i:s'),
            'status' => self::STATUS_ACTIVE
        ];
        
        $this->csvService->appendCSV(self::BOOKINGS_CSV, [$newBooking]);
        
        return $newBooking;
    }

    public function updateBookingComment(int $bookingId, string $comment): void {
        $bookings = $this->csvService->readCSV(self::BOOKINGS_CSV);

        $isFound = false;
        foreach($bookings as &$booking) {
            if ($booking['id'] == $bookingId) {
                $isFound = true;
                $booking['comment'] = $comment;
                break;
            }
        }

        if ($isFound) {
            $this->csvService->writeCSV(self::BOOKINGS_CSV, $bookings);
        } else {
            throw new \RuntimeException("The booking with id $bookingId does not exists!");
        }
    }

    public function updateBooking(array $booking): void {
        $bookings = $this->findAllBookings();

        foreach ($bookings as &$b) {
            if ($b['id'] == $booking['id']) {
                $b = $booking;
                break;
            }
        }

        $this->csvService->writeCSV(self::BOOKINGS_CSV, $bookings);
    }
}