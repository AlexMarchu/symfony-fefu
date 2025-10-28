<?php

namespace App\Controller;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController {

    public function __construct(private BookingRepository $bookingRepository) {}

    #[Route('/', name: 'home')]
    public function home(): Response {
        return new Response("Well, hello there...");
    }

    #[Route('/api/houses/available', name: 'available_houses', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse {
        $result = $this->bookingRepository->findAllAvailableHouses();

        if ($result['error']) {
            return $this->json([
                'success' => false,
                'message' => $result['error']
            ], $result['code']);
        }

        return $this->json([
            'success' => true,
            'data' => $result['data'],
            'count' => count($result['data'])
        ], $result['code']);
    }

    #[Route('/api/bookings', name: 'create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['house_id']) || !isset($data['phone'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required field: house_id or phone!'
            ], Response::HTTP_BAD_REQUEST);
        }

        $houseId = (int)$data['house_id'];
        $phone = trim($data['phone']);
        $comment = $data['comment'] ?? '';

        if (empty($phone)) {
            return $this->json([
                'success' => false,
                'message' => 'Phone number can not be empty'
            ], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->bookingRepository->createBooking($houseId, $phone, $comment);
        
        if ($result['error']) {
            return $this->json([
                'success' => false,
                'message' => $result['error']
            ], $result['code']);
        }

        return $this->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $result['data']
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/bookings/{id}', name: 'update_booking_comment', methods: ['PUT'])]
    public function updateBookingComment(int $id, Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment'])) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required field: comment!'
            ], Response::HTTP_BAD_REQUEST);
        }

        $comment = trim($data['comment']);

        $result = $this->bookingRepository->updateBookingComment($id, $comment);
        
        if ($result['error']) {
            return $this->json([
                'success' => false,
                'message' => $result['error']
            ], $result['code']);
        }

        return $this->json([
            'success' => true,
            'message' => 'Booking comment updated successfully',
            'data' => $result['data']
        ], Response::HTTP_OK);
    }
}