<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AccessTokenService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserService $userService,
        private AccessTokenService $accessTokenService,
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['name', 'phone', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing required field: {$field}!");
            }
        }

        $name = trim($data['name']);
        $phone = trim($data['phone']);
        $plainPassword = $data['password'];

        if (empty($phone)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Phone number can not be empty!');
        }

        try {
            $existingUser = $this->userRepository->findByPhone($phone);

            if ($existingUser) {
                throw new HttpException(Response::HTTP_CONFLICT, "User with phone {$phone} already exists!");
            }

            $user = $this->userService->create($name, $phone, $plainPassword);
            $accessToken = $this->accessTokenService->create($user);
            $this->em->flush();

            $expiresAt = $accessToken->getExpiresAt();
            if (null === $expiresAt) {
                throw new \RuntimeException('Access token expiration date is null');
            }

            return $this->json([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                ],
                'access_token' => [
                    'value' => $accessToken->getValue(),
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_CREATED);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $requiredFields = ['phone', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing required field: {$field}!");
            }
        }

        $phone = trim($data['phone']);
        $plainPassword = $data['password'];

        try {
            $user = $this->userRepository->findByPhone($phone);

            if (!$user) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "User with phone {$phone} not found!");
            }

            if (!$this->userService->isPasswordValid($user, $plainPassword)) {
                throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid password!');
            }

            $accessToken = $this->accessTokenService->create($user);
            $this->em->flush();

            $expiresAt = $accessToken->getExpiresAt();
            if (null === $expiresAt) {
                throw new \RuntimeException('Access token expiration date is null');
            }

            return $this->json([
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles(),
                ],
                'access_token' => [
                    'value' => $accessToken->getValue(),
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                ]
            ], Response::HTTP_OK);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $this->accessTokenService->removeByUser($user);
            $this->em->flush();

            return $this->json([
                'message' => 'Successfully logged out'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, "Internal server error: {$e->getMessage()}");
        }
    }
}
