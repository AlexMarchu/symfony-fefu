<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Repository\UserRepository;
use App\Service\BookingSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController {

    public function __construct(
        private BookingRepository $bookingRepository,
        private HouseRepository $houseRepository,
        private UserRepository $userRepository,
        private BookingSerializer $bookingSerializer
    ) {}

    #[Route('/', name: 'home')]
    public function home(): Response {
        return new Response("Well, hello there...");
    }

    #[Route('/api/bookings', name: 'all_bookings', methods: ['GET'])]
    public function getAllBookings(): JsonResponse {
        try {
            $allBookings = $this->bookingRepository->findAll();
            $response = $this->bookingSerializer->serializeCollection($allBookings);

            return $this->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }

    #[Route('/api/bookings/create', name: 'create_booking', methods: ['POST'])]
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
                'Phone number can not be empty!'
            );
        }

        try {
            $house = $this->houseRepository->find($houseId);

            if (!$house)
                throw new NotFoundHttpException("House with id $houseId not found!");

            $existingBooking = $this->bookingRepository->findActiveBookingByHouseId($houseId);
            if ($existingBooking)
                throw new UnprocessableEntityHttpException("The house with id $houseId is not available!");

            $user = $this->userRepository->findByPhone($phone);
            if (!$user)
                throw new NotFoundHttpException("User with phone $phone not found!");

            $booking = new Booking($house, $user, $comment);

            $this->bookingRepository->save($booking);

            $response = $this->bookingSerializer->serialize($booking);

            return $this->json($response, Response::HTTP_CREATED);
            
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }

    #[Route('/api/bookings/{id}/comment', name: 'update_booking_comment', methods: ['PUT'])]
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
            $booking = $this->bookingRepository->find($id);
            if (!$booking)
                throw new NotFoundHttpException("Booking with id $id not found!");

            $booking->setComment($comment);
            $this->bookingRepository->save($booking);

            $response = $this->bookingSerializer->serialize($booking);

            return $this->json($response, Response::HTTP_OK);
            
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                "Internal server error: {$e->getMessage()}"
            );
        }
    }

    #[Route('/api/bookings/{id}', name: 'delete_booking', methods: ['DELETE'])]
    public function deleteBooking(int $id): JsonResponse {
        try {
            $booking = $this->bookingRepository->find($id);
            
            if (!$booking) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    "Booking with id $id not found"
                );
            }

            $this->bookingRepository->remove($booking);

            return $this->json(
                ['message' => "Booking with id $id deleted successfully"],
                Response::HTTP_OK
            );
            
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