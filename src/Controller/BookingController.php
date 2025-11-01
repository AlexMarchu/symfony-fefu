<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BookingController extends AbstractController {

    public function __construct(private BookingRepository $bookingRepository) {}

    #[Route('/', name: 'home')]
    public function home(): Response {
        return new Response("Well, hello there...");
    }

    #[Route('/api/houses/available', name: 'available_houses', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse {
        try {
            $availableHouses = $this->bookingRepository->findAllAvailableHouses();
            
            return $this->json($availableHouses, Response::HTTP_OK);
            
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }

    #[Route('/api/bookings', name: 'create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['house_id']) || !isset($data['phone'])) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'Missing required field: house_id or phone!'
            );
        }

        $houseId = (int)$data['house_id'];
        $phone = trim($data['phone']);
        $comment = $data['comment'] ?? '';

        if (empty($phone)) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'Phone number can not be empty'
            );
        }

        try {
            $house = $this->bookingRepository->findHouseById($houseId);
            if (!$house) {
                throw new NotFoundHttpException("House with id $houseId not found");
            }

            if (!$house['is_available']) {
                throw new UnprocessableEntityHttpException("The house with id $houseId is not available");
            }

            $bookingData = [
                'house_id' => $houseId,
                'phone' => $phone,
                'comment' => $comment
            ];
            
            $newBooking = $this->bookingRepository->createBooking($bookingData);
            
            $house['is_available'] = 0;
            $this->bookingRepository->updateHouse($house);

            return $this->json($newBooking, Response::HTTP_CREATED);
            
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }

    #[Route('/api/bookings/{id}', name: 'update_booking_comment', methods: ['PUT'])]
    public function updateBookingComment(int $id, Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'])) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'Missing required field: comment!'
            );
        }

        $comment = trim($data['comment']);

        try {
            $booking = $this->bookingRepository->findBookingById($id);
            if (!$booking) {
                throw new NotFoundHttpException("Booking with id $id not found");
            }

            $booking['comment'] = $comment;
            $this->bookingRepository->updateBooking($booking);

            return $this->json($booking, Response::HTTP_OK);
            
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }
}