<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

/**
 * Contract tests for Auth API endpoints.
 *
 * Covers login, me (profile), and validation error responses.
 * These are critical since every frontend consumer depends on auth.
 */
class AuthApiTest extends PactTestCase
{
    public function testLoginSuccess(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/login');
        $request->addHeader('Content-Type', 'application/json');
        // Remove auth header for login — it's not needed
        $request->setBody([
            'email' => 'admin@senselab.com',
            'password' => 'password123',
        ]);

        $responseBody = [
            'success' => $this->matcher->boolean(true),
            'data' => [
                'token' => $this->matcher->like('1|random-sanctum-token-string'),
                'user' => [
                    'id' => $this->matcher->integerV3(1),
                    'nombre' => $this->matcher->like('Admin'),
                    'email' => $this->matcher->email(),
                ],
            ],
        ];

        $this->builder
            ->given('user admin@senselab.com exists with password')
            ->uponReceiving('a valid login request')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $responseBody));

        $response = $this->callMockServer('POST', '/api/login', [
            'email' => 'admin@senselab.com',
            'password' => 'password123',
        ]);
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testLoginInvalidCredentials(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/login');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody([
            'email' => 'bad@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->builder
            ->given('no user with email bad@example.com')
            ->uponReceiving('a login request with invalid credentials')
            ->with($request)
            ->willRespondWith($this->jsonResponse(401, $this->errorEnvelope('Credenciales inválidas')));

        $response = $this->callMockServer('POST', '/api/login', [
            'email' => 'bad@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertSame(401, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetCurrentUser(): void
    {
        $responseBody = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'nombre' => $this->matcher->like('Admin'),
            'email' => $this->matcher->email(),
            'roles' => $this->matcher->eachLike([
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('admin'),
            ]),
        ]);

        $this->builder
            ->given('authenticated user exists')
            ->uponReceiving('a request to get current user profile')
            ->with($this->authenticatedRequest('GET', '/api/me'))
            ->willRespondWith($this->jsonResponse(200, $responseBody));

        $response = $this->callMockServer('GET', '/api/me');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testUnauthenticatedRequest(): void
    {
        $request = new \PhpPact\Consumer\Model\ConsumerRequest();
        $request->setMethod('GET');
        $request->setPath('/api/me');
        $request->addHeader('Accept', 'application/json');
        // No Authorization header

        $this->builder
            ->given('no auth token provided')
            ->uponReceiving('an unauthenticated request to protected endpoint')
            ->with($request)
            ->willRespondWith($this->jsonResponse(401, $this->errorEnvelope('Unauthenticated')));

        $response = $this->callMockServer('GET', '/api/me', null, false);
        $this->assertSame(401, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testValidationErrorResponse(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/login');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody([
            'email' => '',
            'password' => '',
        ]);

        $responseBody = [
            'success' => $this->matcher->boolean(false),
            'message' => $this->matcher->like('Los datos proporcionados no son válidos'),
            'errors' => [
                'email' => $this->matcher->eachLike($this->matcher->like('El campo email es obligatorio.')),
            ],
        ];

        $this->builder
            ->given('validation is active')
            ->uponReceiving('a request with missing required fields')
            ->with($request)
            ->willRespondWith($this->jsonResponse(422, $responseBody));

        $response = $this->callMockServer('POST', '/api/login', [
            'email' => '',
            'password' => '',
        ]);
        $this->assertSame(422, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
