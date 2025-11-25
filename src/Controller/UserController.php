<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private UserSerializer $userSerializer,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/api/users', name: 'all_users', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        try {
            $allUsers = $this->userRepository->findAll();
            $response = $this->userSerializer->serializeCollection($allUsers);

            return $this->json($response, Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/api/users/create', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['name', 'phone', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing required field: {$field}");
            }
        }

        $phone = trim($data['phone']);
        $name = trim($data['name']);
        $password = $data['password'];

        if (empty($phone)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Phone number can not be empty');
        }

        try {
            $existingUser = $this->userRepository->findByPhone($phone);
            if ($existingUser) {
                throw new HttpException(Response::HTTP_CONFLICT, "User with phone {$phone} already exists!");
            }

            $user = new User($name, $phone);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);

            $response = $this->userSerializer->serialize($user);

            return $this->json($response, Response::HTTP_CREATED);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "User with id {$id} not found");
            }

            $bookings = $user->getBookings();
            $bookingCount = $bookings->count();
            $bookingIds = array_map(fn ($b) => $b->getId(), $bookings->toArray());

            $this->userRepository->remove($user);

            return $this->json([
                'message' => "User with id {$id} and all associated bookings deleted successfully",
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
