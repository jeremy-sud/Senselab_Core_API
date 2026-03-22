<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

/**
 * Contract tests for the Ventas API endpoints.
 *
 * Covers list, show, create operations for ventas (sales),
 * including related nested resources (detalles, cliente, factura electrónica).
 */
class VentaApiTest extends PactTestCase
{
    public function testGetVentasList(): void
    {
        $ventaShape = [
            'id' => $this->matcher->integerV3(1),
            'numero_venta' => $this->matcher->like('V-0001'),
            'fecha_venta' => $this->iso8601(),
            'subtotal' => $this->matcher->decimalV3(10000.00),
            'descuento' => $this->matcher->decimalV3(0.00),
            'impuestos' => $this->matcher->decimalV3(1300.00),
            'total' => $this->matcher->decimalV3(11300.00),
            'estado' => $this->matcher->like('completada'),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/ventas');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('ventas exist')
            ->uponReceiving('a request to list ventas')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($ventaShape)));

        $response = $this->callMockServer('GET', '/api/ventas?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetVentaById(): void
    {
        $ventaDetail = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'numero_venta' => $this->matcher->like('V-0001'),
            'fecha_venta' => $this->iso8601(),
            'cliente' => [
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('Cliente Demo S.A.'),
                'identificacion' => $this->matcher->like('123456789'),
                'email' => $this->matcher->email(),
            ],
            'subtotal' => $this->matcher->decimalV3(10000.00),
            'descuento' => $this->matcher->decimalV3(0.00),
            'impuestos' => $this->matcher->decimalV3(1300.00),
            'total' => $this->matcher->decimalV3(11300.00),
            'estado' => $this->matcher->like('completada'),
            'observaciones' => $this->matcher->like(''),
            'detalles' => $this->matcher->eachLike([
                'id' => $this->matcher->integerV3(1),
                'producto_id' => $this->matcher->integerV3(1),
                'producto_nombre' => $this->matcher->like('Producto A'),
                'cantidad' => $this->matcher->decimalV3(2.00),
                'precio_unitario' => $this->matcher->decimalV3(5000.00),
                'descuento' => $this->matcher->decimalV3(0.00),
                'impuesto' => $this->matcher->decimalV3(650.00),
                'subtotal' => $this->matcher->decimalV3(10000.00),
                'total' => $this->matcher->decimalV3(10650.00),
            ]),
            'creado_en' => $this->iso8601(),
            'actualizado_en' => $this->iso8601(),
        ]);

        $this->builder
            ->given('venta with id 1 exists with detalles')
            ->uponReceiving('a request to get venta detail by id')
            ->with($this->authenticatedRequest('GET', '/api/ventas/1'))
            ->willRespondWith($this->jsonResponse(200, $ventaDetail));

        $response = $this->callMockServer('GET', '/api/ventas/1');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testCreateVenta(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/ventas');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody([
            'cliente_id' => 1,
            'forma_pago_id' => 1,
            'observaciones' => 'Venta de prueba',
            'detalles' => [
                [
                    'producto_id' => 1,
                    'cantidad' => 2,
                    'precio_unitario' => 5000.00,
                    'descuento' => 0,
                ],
            ],
        ]);

        $responseBody = [
            'success' => $this->matcher->boolean(true),
            'message' => $this->matcher->like('Recurso creado exitosamente'),
            'data' => [
                'id' => $this->matcher->integerV3(1),
                'numero_venta' => $this->matcher->like('V-0001'),
                'total' => $this->matcher->decimalV3(11300.00),
                'estado' => $this->matcher->like('completada'),
                'creado_en' => $this->iso8601(),
            ],
        ];

        $this->builder
            ->given('empresa context and products exist')
            ->uponReceiving('a request to create a venta')
            ->with($request)
            ->willRespondWith($this->jsonResponse(201, $responseBody));

        $response = $this->callMockServer('POST', '/api/ventas', [
            'cliente_id' => 1,
            'forma_pago_id' => 1,
            'observaciones' => 'Venta de prueba',
            'detalles' => [
                ['producto_id' => 1, 'cantidad' => 2, 'precio_unitario' => 5000.00, 'descuento' => 0],
            ],
        ]);
        $this->assertSame(201, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
