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

    public function findAllAvailableHouses(): array {
        $response = [
            'data' => null,
            'error' => null,
            'code' => Response::HTTP_OK
        ];

        try {
            $houses = $this->csvService->readCSV(self::HOUSES_CSV);

            $data = [];
            foreach($houses as $house) {
                if (!$house['is_available']) 
                    continue;
                $data[] = $house;
            }
            $response['data'] = $data;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $response['code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $response;
    }

    public function createBooking(int $houseId, string $phone, string $comment): array {
        $response = [
            'data' => null,
            'error' => null,
            'code' => Response::HTTP_OK
        ];

        try {
            $houses = $this->csvService->readCSV(self::HOUSES_CSV);

            $isHouseAvailable = false;
            foreach($houses as &$house) {
                if ($house['id'] == $houseId && $house['is_available']) {
                    $isHouseAvailable = true;
                    $house['is_available'] = 0;
                    break;
                }
            }

            if (!$isHouseAvailable) {
                $response['error'] = "The house with id $houseId is not available now or does not exists!";
                $response['code'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            } else {
                $newId = 1;
                if (!empty($bookings)) {
                    $maxId = max(array_column($bookings, 'id'));
                    $newId = $maxId + 1;
                }

                $newBooking = [
                    'id' => $newId,
                    'house_id' => $houseId,
                    'phone' => $phone,
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s'),
                    'status' => self::STATUS_ACTIVE
                ];

                $this->csvService->writeCSV(self::HOUSES_CSV, $houses);
                $this->csvService->appendCSV(self::BOOKINGS_CSV, [$newBooking]);
            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $response['code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $response;
    }

    public function updateBookingComment(int $bookingId, string $comment): array {
        $response = [
            'data' => null,
            'error' => null,
            'code' => Response::HTTP_OK
        ];
        
        try {
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
                $response['error'] = "The booking with id $bookingId does not exists!";
                $response['code'] = Response::HTTP_UNPROCESSABLE_ENTITY;
            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $response['code'] = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $response;
    }
}