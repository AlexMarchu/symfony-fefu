<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractAPITestCase extends WebTestCase
{
    protected $client;
    protected $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->clearDatabase();
    }

    protected function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM users');
        $connection->executeStatement('DELETE FROM houses');
        $connection->executeStatement('DELETE FROM bookings');
    }

    protected function createUser(string $name, string $phone): User
    {
        $user = new User($name, $phone);

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

    protected function makeRequest(string $method, string $url, ?array $data = null): array
    {
        $this->client->request(
            $method,
            $url,
            [],
            [],
            ['Content-Type' => 'application/json'],
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
}
