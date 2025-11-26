<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\AccessToken;
use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractAPITestCase extends WebTestCase
{
    protected $client;
    protected $entityManager;
    protected $accessToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->clearDatabase();
        $this->createTestUserAndToken();
    }

    protected function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM access_tokens');
        $connection->executeStatement('DELETE FROM bookings');
        $connection->executeStatement('DELETE FROM users');
        $connection->executeStatement('DELETE FROM houses');
    }

    protected function createTestUserAndToken(): void
    {
        $user = new User('Test User', '+70123012304', ['ROLE_USER']);
        $user->setPassword('$2y$13$abcdefghijklmnopqrstuv');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $accessToken = new AccessToken();
        $accessToken->setUser($user);
        $accessToken->setValue('test_access_token');
        $accessToken->setExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        $this->accessToken = 'test_access_token';
    }

    protected function createUser(string $name, string $phone, array $roles = ['ROLE_USER']): User
    {
        $user = new User($name, $phone, $roles);
        $user->setPassword(password_hash('password', PASSWORD_DEFAULT));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createHouse(string $name, int $sleepingPlaces, int $distanceToSea): House
    {
        $house = new House($name, $sleepingPlaces, $distanceToSea);

        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    protected function createBooking(User $user, House $house, string $comment = ''): Booking
    {
        $booking = new Booking($house, $user, $comment);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }

    protected function makeRequest(string $method, string $url, ?array $data = null, bool $authenticated = true): array
    {
        $headers = ['Content-Type' => 'application/json'];

        if ($authenticated && $this->accessToken) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->accessToken;
        }

        $this->client->request(
            $method,
            $url,
            [],
            [],
            $headers,
            $data ? json_encode($data) : null
        );

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        return [
            'status' => $statusCode,
            'content' => json_decode($response->getContent(), true),
            'successful' => $statusCode >= 200 && $statusCode < 300,
        ];
    }

    protected function loginAndGetToken(string $phone = '+79999999999', string $password = 'password'): string
    {
        $loginData = [
            'phone' => $phone,
            'password' => $password,
        ];

        $response = $this->makeRequest('POST', '/api/auth/login', $loginData, false);

        if ($response['successful']) {
            return $response['content']['access_token']['value'];
        }

        return '';
    }
}
