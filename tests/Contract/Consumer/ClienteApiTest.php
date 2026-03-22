<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

class ClienteApiTest extends PactTestCase
{
    public function testGetClientesList(): void
    {
        $clienteShape = [
            'id' => $this->matcher->integerV3(1),
            'tipo_identificacion' => $this->matcher->regex('01', '^0[1-4]$'),
            'identificacion' => $this->matcher->like('123456789'),
            'nombre' => $this->matcher->like('Cliente Demo S.A.'),
            'nombre_comercial' => $this->matcher->like('Cliente Demo'),
            'email' => $this->matcher->email(),
            'telefono' => $this->matcher->like('22223333'),
            'direccion' => $this->matcher->like('San José, Costa Rica'),
            'activo' => $this->matcher->boolean(true),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/clientes');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('clientes exist')
            ->uponReceiving('a request to list clientes')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($clienteShape)));

        $response = $this->callMockServer('GET', '/api/clientes?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetClienteById(): void
    {
        $clienteDetail = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'tipo_identificacion' => $this->matcher->regex('01', '^0[1-4]$'),
            'identificacion' => $this->matcher->like('123456789'),
            'nombre' => $this->matcher->like('Cliente Demo S.A.'),
            'nombre_comercial' => $this->matcher->like('Cliente Demo'),
            'email' => $this->matcher->email(),
            'telefono' => $this->matcher->like('22223333'),
            'celular' => $this->matcher->like('88887777'),
            'direccion' => $this->matcher->like('San José, Costa Rica'),
            'provincia' => $this->matcher->like('San José'),
            'canton' => $this->matcher->like('Central'),
            'distrito' => $this->matcher->like('Carmen'),
            'tipo_cliente' => $this->matcher->like('regular'),
            'activo' => $this->matcher->boolean(true),
            'creado_en' => $this->iso8601(),
        ]);

        $this->builder
            ->given('cliente with id 1 exists')
            ->uponReceiving('a request to get cliente by id')
            ->with($this->authenticatedRequest('GET', '/api/clientes/1'))
            ->willRespondWith($this->jsonResponse(200, $clienteDetail));

        $response = $this->callMockServer('GET', '/api/clientes/1');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testCreateCliente(): void
    {
        $body = [
            'tipo_identificacion' => '01',
            'identificacion' => '987654321',
            'nombre' => 'Nuevo Cliente S.A.',
            'email' => 'nuevo@example.com',
            'telefono' => '22221111',
            'direccion' => 'Heredia, Costa Rica',
            'provincia' => 'Heredia',
            'canton' => 'Central',
            'distrito' => 'Mercedes',
        ];

        $request = $this->authenticatedRequest('POST', '/api/clientes');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody($body);

        $responseBody = [
            'success' => $this->matcher->boolean(true),
            'message' => $this->matcher->like('Recurso creado exitosamente'),
            'data' => [
                'id' => $this->matcher->integerV3(2),
                'tipo_identificacion' => $this->matcher->like('01'),
                'identificacion' => $this->matcher->like('987654321'),
                'nombre' => $this->matcher->like('Nuevo Cliente S.A.'),
                'email' => $this->matcher->email(),
                'creado_en' => $this->iso8601(),
            ],
        ];

        $this->builder
            ->given('empresa context is set')
            ->uponReceiving('a request to create a cliente')
            ->with($request)
            ->willRespondWith($this->jsonResponse(201, $responseBody));

        $response = $this->callMockServer('POST', '/api/clientes', $body);
        $this->assertSame(201, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetClienteNotFound(): void
    {
        $this->builder
            ->given('no cliente with id 9999 exists')
            ->uponReceiving('a request to get non-existent cliente')
            ->with($this->authenticatedRequest('GET', '/api/clientes/9999'))
            ->willRespondWith($this->jsonResponse(404, $this->errorEnvelope('No query results for model')));

        $response = $this->callMockServer('GET', '/api/clientes/9999');
        $this->assertSame(404, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
