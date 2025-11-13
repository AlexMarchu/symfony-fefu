<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\AbstractAPITestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 *
 * @coversNothing
 */
class UserAPITest extends AbstractAPITestCase
{
    public function testGetAllUsers(): void
    {
        $user1 = $this->createUser('Vasya', '+79111111111');
        $user2 = $this->createUser('Petya', '+79222222222');

        $response = $this->makeRequest('GET', '/api/users');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(2, $data);
    }

    public function testGetAllUsersWhenEmpty(): void
    {
        $response = $this->makeRequest('GET', '/api/users');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(0, $data);
    }

    public function testCreateUserSuccess(): void
    {
        $userData = [
            'name' => 'VALERAUSSR',
            'phone' => '+79999999999',
        ];

        $response = $this->makeRequest('POST', '/api/users/create', $userData);
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals($userData['name'], $data['name']);
        $this->assertEquals($userData['phone'], $data['phone']);
    }

    public function testCreateUserMissingFields(): void
    {
        $userData = [
            'name' => 'VALERAUSSR',
        ];

        $response = $this->makeRequest('POST', '/api/users/create', $userData);
        $data = $response['content'];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testCreateUserDuplicatePhone(): void
    {
        $this->createUser('Vasya', '+79999999999');

        $userData = [
            'name' => 'Petya',
            'phone' => '+79999999999',
        ];

        $response = $this->makeRequest('POST', '/api/users/create', $userData);

        $this->assertEquals(Response::HTTP_CONFLICT, $response['status']);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $userId = $user->getId();

        $response = $this->makeRequest('DELETE', "/api/users/{$userId}");
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertStringContainsString('deleted successfully', $data['message']);

        $deletedUser = $this->entityManager->getRepository('App\Entity\User')->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testDeleteUserWithBookings(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $house = $this->createHouse('Test House', 2, 75);
        $booking = $this->createBooking($user, $house);
        $userId = $user->getId();
        $bookingId = $booking->getId();

        $response = $this->makeRequest('DELETE', "/api/users/{$userId}");
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertArrayHasKey('deleted_bookings_count', $data);
        $this->assertEquals(1, $data['deleted_bookings_count']);

        $deletedUser = $this->entityManager->getRepository('App\Entity\User')->find($userId);
        $deletedBooking = $this->entityManager->getRepository('App\Entity\Booking')->find($bookingId);

        $this->assertNull($deletedUser);
        $this->assertNull($deletedBooking);
    }

    public function testDeleteUserNotFound(): void
    {
        $response = $this->makeRequest('DELETE', '/api/users/777');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }
}
