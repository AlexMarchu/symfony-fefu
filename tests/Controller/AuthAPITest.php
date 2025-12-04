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
class AuthAPITest extends AbstractAPITestCase
{
    public function testRegisterSuccess(): void
    {
        $userData = [
            'name' => 'New User',
            'phone' => '+78888888888',
            'password' => 'password123',
        ];

        $response = $this->makeRequest('POST', '/api/auth/register', $userData, false);
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals($userData['name'], $data['user']['name']);
        $this->assertEquals($userData['phone'], $data['user']['phone']);
        $this->assertArrayHasKey('access_token', $data);
    }

    public function testRegisterMissingFields(): void
    {
        $userData = [
            'name' => 'New User',
            // missing phone and password
        ];

        $response = $this->makeRequest('POST', '/api/auth/register', $userData, false);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response['status']);
    }

    public function testRegisterDuplicatePhone(): void
    {
        $this->createUser('Existing User', '+78888888888');

        $userData = [
            'name' => 'New User',
            'phone' => '+78888888888',
            'password' => 'password123',
        ];

        $response = $this->makeRequest('POST', '/api/auth/register', $userData, false);
        $this->assertEquals(Response::HTTP_CONFLICT, $response['status']);
    }

    public function testLoginSuccess(): void
    {
        $this->createUser('Test User', '+78888888888');

        $loginData = [
            'phone' => '+78888888888',
            'password' => 'password',
        ];

        $response = $this->makeRequest('POST', '/api/auth/login', $loginData, false);
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals('Test User', $data['user']['name']);
        $this->assertArrayHasKey('access_token', $data);
    }

    public function testLoginUserNotFound(): void
    {
        $loginData = [
            'phone' => '+70000000000',
            'password' => 'password',
        ];

        $response = $this->makeRequest('POST', '/api/auth/login', $loginData, false);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testLoginInvalidPassword(): void
    {
        $this->createUser('Test User', '+78888888888');

        $loginData = [
            'phone' => '+78888888888',
            'password' => 'wrong_password',
        ];

        $response = $this->makeRequest('POST', '/api/auth/login', $loginData, false);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response['status']);
    }

    public function testLogoutSuccess(): void
    {
        $response = $this->makeRequest('POST', '/api/auth/logout');
        $data = $response['content'];

        $this->assertTrue($response['successful']);
        $this->assertEquals('Successfully logged out', $data['message']);
    }
}
