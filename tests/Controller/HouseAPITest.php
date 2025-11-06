<?php

namespace App\Tests\Controller;

use App\Tests\AbstractAPITestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseAPITest extends AbstractApiTestCase {

    public function testGetAllHouses(): void {
        $house1 = $this->createHouse('House 1', 3, 60);
        $house2 = $this->createHouse('House 2', 4, 80);

        $response = $this->makeRequest('GET', '/api/houses');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(2, $data);
        $this->assertEquals($house1->getName(), $data[0]['name']);
        $this->assertEquals($house2->getName(), $data[1]['name']);
    }

    public function testGetAllHousesWhenEmpty(): void {
        $response = $this->makeRequest('GET', '/api/houses');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(0, $data);
    }

    public function testGetAvailableHouses(): void {
        $houseAvailable = $this->createHouse("Available House", 2, 45);
        $houseBooked = $this->createHouse("Booked House", 3, 150);
        
        $user = $this->createUser("Pavel", "+79146765071");

        $booking = $this->createBooking($user, $houseBooked);

        $response = $this->makeRequest('GET', '/api/houses/available');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(1, $data);
        $this->assertEquals($houseAvailable->getName(), $data[0]['name']);
    }

    public function testGetAvailableHousesWhenEmpty(): void {
        $house1 = $this->createHouse('House 1', 3, 60);
        $house2 = $this->createHouse('House 2', 4, 80);
        
        $user = $this->createUser("VALERAUSSR", "+79999999999");

        $booking1 = $this->createBooking($user, $house1);
        $booking2 = $this->createBooking($user, $house2);

        $response = $this->makeRequest('GET', '/api/houses/available');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertCount(0, $data);
    }

    public function testCreateHouseSuccess(): void {
        $houseData = [
            'name' => 'Test House',
            'sleeping_places' => 3,
            'distance_to_sea' => 80
        ];

        $response = $this->makeRequest('POST', '/api/houses/create', $houseData);
        $data = $response['content'];
        
        $this->assertTrue($response['successful']);
        $this->assertEquals('Test House', $data['name']);
        $this->assertEquals(3, $data['sleeping_places']);
        $this->assertEquals(80, $data['distance_to_sea']);
    }

    public function testCreateHouseMissingFields(): void {
        $houseData = [
            'name' => 'Incomplete House :('
        ];

        $response = $this->makeRequest('POST', '/api/houses/create', $houseData);
        
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testDeleteHouseSuccess(): void {
        $house = $this->createHouse("Test House", 2, 75);
        $houseId = $house->getId();

        $response = $this->makeRequest('DELETE', "/api/houses/{$houseId}");
        $data = $response['content'];
        
        $this->assertTrue($response['successful']);
        $this->assertStringContainsString('deleted successfully', $data['message']);
        
        $deletedHouse = $this->entityManager->getRepository('App\Entity\House')->find($houseId);
        $this->assertNull($deletedHouse);
    }

    public function testDeleteHouseWithBookings(): void {
        $house = $this->createHouse("Test House", 2, 75);
        $user = $this->createUser("VALERAUSSR", "+79999999999");
        $booking = $this->createBooking($user, $house);

        $response = $this->makeRequest('DELETE', "/api/houses/{$house->getId()}");
        $data = $response['content'];
        
        $this->assertTrue($response['successful']);
        $this->assertArrayHasKey('deleted_bookings_count', $data);
        $this->assertEquals(1, $data['deleted_bookings_count']);
    }

    public function testDeleteHouseNotFound(): void {
        $response = $this->makeRequest('DELETE', '/api/houses/777');
        
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }
}