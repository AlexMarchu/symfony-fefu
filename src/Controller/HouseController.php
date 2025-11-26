<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\House;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Service\HouseSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class HouseController extends AbstractController
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private HouseRepository $houseRepository,
        private HouseSerializer $houseSerializer,
    ) {
    }

    #[Route('/api/houses', name: 'all_houses', methods: ['GET'])]
    public function getAllHouses(): JsonResponse
    {
        try {
            $allHouses = $this->houseRepository->findAll();
            $response = $this->houseSerializer->serializeCollection($allHouses);

            return $this->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/api/houses/available', name: 'available_houses', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse
    {
        try {
            $availableHouses = $this->houseRepository->findAllAvailableHouses();
            $response = $this->houseSerializer->serializeCollection($availableHouses);

            return $this->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/api/houses/create', name: 'create_house', methods: ['POST'])]
    public function createHouse(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $requiredFields = ['name', 'sleeping_places', 'distance_to_sea'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing required field: {$field}");
                }
            }

            $name = trim($data['name']);
            $sleepingPlaces = (int) $data['sleeping_places'];
            $distanceToSea = (int) $data['distance_to_sea'];

            $house = new House($name, $sleepingPlaces, $distanceToSea);

            $this->houseRepository->save($house);
            $response = $this->houseSerializer->serialize($house);

            return $this->json($response, Response::HTTP_CREATED);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/api/houses/{id}', name: 'delete_house', methods: ['DELETE'])]
    public function deleteHouse(int $id): JsonResponse
    {
        try {
            $house = $this->houseRepository->find($id);

            if (!$house) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "House with id {$id} not found");
            }

            $bookings = $this->bookingRepository->findByHouse($house);
            $bookingCount = count($bookings);
            $bookingIds = array_map(fn ($b) => $b->getId(), $bookings);

            $this->houseRepository->remove($house);

            return $this->json([
                'message' => "House with id {$id} and all associated bookings deleted successfully",
                'deleted_bookings_count' => $bookingCount,
                'deleted_booking_ids' => $bookingIds,
            ], Response::HTTP_OK);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }
}
