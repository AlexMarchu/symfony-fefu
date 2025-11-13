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
class BookingAPITest extends AbstractAPITestCase
{
    public function testGetAllBookings(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $house = $this->createHouse('Test House', 2, 75);
        $booking = $this->createBooking($user, $house);

        $response = $this->makeRequest('GET', '/api/bookings');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(1, $data);
        $this->assertEquals($booking->getId(), $data[0]['id']);
    }

    public function testGetAllBookingsWhenEmpty(): void
    {
        $response = $this->makeRequest('GET', '/api/bookings');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(0, $data);
    }

    public function testCreateBookingSuccess(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $house = $this->createHouse('Test House', 2, 75);

        $bookingData = [
            'house_id' => $house->getId(),
            'phone' => $user->getPhone(),
            'comment' => 'Test booking comment',
        ];

        $response = $this->makeRequest('POST', '/api/bookings/create', $bookingData);
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals($house->getId(), $data['house_id']);
        $this->assertEquals($user->getId(), $data['user_id']);
        $this->assertEquals('Test booking comment', $data['comment']);
        $this->assertEquals('active', $data['status']);
    }

    public function testCreateBookingMissingFields(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $house = $this->createHouse('Test House', 2, 75);

        $bookingData = [
            'house_id' => $house->getId(),
            'comment' => 'Test booking comment',
        ];

        $response = $this->makeRequest('POST', '/api/bookings/create', $bookingData);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testCreateBookingUserNotFound(): void
    {
        $house = $this->createHouse('Test House', 2, 75);

        $bookingData = [
            'phone' => '+79999999999',
            'house_id' => $house->getId(),
        ];

        $response = $this->makeRequest('POST', '/api/bookings/create', $bookingData);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $house = $this->createHouse('Test House', 2, 75);

        $bookingData = [
            'phone' => '+79999999999',
            'house_id' => '777',
        ];

        $response = $this->makeRequest('POST', '/api/bookings/create', $bookingData);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testCreateBookingHouseNotAvailable(): void
    {
        $house = $this->createHouse('Test House', 2, 75);
        $user1 = $this->createUser('Vasya', '+79111111111');
        $user2 = $this->createUser('Petya', '+79222222222');

        $this->createBooking($user1, $house);

        $bookingData = [
            'house_id' => $house->getId(),
            'phone' => $user2->getPhone(),
        ];

        $response = $this->makeRequest('POST', '/api/bookings/create', $bookingData);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response['status']);
    }

    public function testUpdateBookingCommentSuccess(): void
    {
        $house = $this->createHouse('Test House', 2, 75);
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $booking = $this->createBooking($user, $house, 'Old comment');

        $commentData = [
            'comment' => 'Updated comment',
        ];

        $response = $this->makeRequest('PUT', "/api/bookings/{$booking->getId()}/comment", $commentData);
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals('Updated comment', $data['comment']);
    }

    public function testUpdateBookingCommentMissingComment(): void
    {
        $house = $this->createHouse('Test House', 2, 75);
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $booking = $this->createBooking($user, $house, 'Old comment');

        $commentData = [

        ];

        $response = $this->makeRequest('PUT', "/api/bookings/{$booking->getId()}/comment", $commentData);
        $data = $response['content'];

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testUpdateBookingCommentNotFound(): void
    {
        $commentData = [
            'comment' => 'Updated comment',
        ];

        $response = $this->makeRequest('PUT', '/api/bookings/777/comment', $commentData);
        $data = $response['content'];

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testDeleteBooking(): void
    {
        $house = $this->createHouse('Test House', 2, 75);
        $user = $this->createUser('VALERAUSSR', '+79999999999');
        $booking = $this->createBooking($user, $house);
        $bookingId = $booking->getId();

        $response = $this->makeRequest('DELETE', "/api/bookings/{$booking->getId()}");

        $this->assertTrue($response['successful']);

        $deletedBooking = $this->entityManager->getRepository('App\Entity\Booking')->find($bookingId);
        $this->assertNull($deletedBooking);
    }

    public function testDeleteBookingNotFound(): void
    {
        $response = $this->makeRequest('DELETE', '/api/bookings/777');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }
}
