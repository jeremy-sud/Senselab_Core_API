<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

/**
 * Contract tests for Inventario API endpoints.
 *
 * Covers entradas (entries) and salidas (exits) of inventory,
 * including nested detalles with product references.
 */
class InventarioApiTest extends PactTestCase
{
    public function testGetEntradasList(): void
    {
        $entradaShape = [
            'id' => $this->matcher->integerV3(1),
            'fecha_entrada' => $this->iso8601(),
            'tipo_entrada' => $this->matcher->regex('Compra', '^(Compra|Traslado|Ajuste|Devolución|Producción|Otro)$'),
            'estado' => $this->matcher->regex('Procesada', '^(Pendiente|Procesada|Cancelada)$'),
            'monto_total' => $this->matcher->decimalV3(50000.00),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/inventario/entradas');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('entradas inventario exist')
            ->uponReceiving('a request to list entradas de inventario')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($entradaShape)));

        $response = $this->callMockServer('GET', '/api/inventario/entradas?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetEntradaById(): void
    {
        $entradaDetail = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'almacen' => [
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('Almacén Principal'),
            ],
            'fecha_entrada' => $this->iso8601(),
            'tipo_entrada' => $this->matcher->like('Compra'),
            'documento_referencia' => $this->matcher->like('OC-001'),
            'estado' => $this->matcher->like('Procesada'),
            'monto_total' => $this->matcher->decimalV3(50000.00),
            'detalles' => $this->matcher->eachLike([
                'id' => $this->matcher->integerV3(1),
                'producto' => [
                    'id' => $this->matcher->integerV3(1),
                    'nombre' => $this->matcher->like('Producto A'),
                    'codigo_barras' => $this->matcher->like('7890001234567'),
                ],
                'cantidad' => $this->matcher->decimalV3(10.00),
                'precio_unitario' => $this->matcher->decimalV3(5000.00),
                'subtotal' => $this->matcher->decimalV3(50000.00),
            ]),
            'creado_en' => $this->iso8601(),
            'actualizado_en' => $this->iso8601(),
        ]);

        $this->builder
            ->given('entrada inventario with id 1 exists with detalles')
            ->uponReceiving('a request to get entrada inventario detail by id')
            ->with($this->authenticatedRequest('GET', '/api/inventario/entradas/1'))
            ->willRespondWith($this->jsonResponse(200, $entradaDetail));

        $response = $this->callMockServer('GET', '/api/inventario/entradas/1');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetSalidasList(): void
    {
        $salidaShape = [
            'id' => $this->matcher->integerV3(1),
            'fecha_salida' => $this->iso8601(),
            'tipo_salida' => $this->matcher->like('Venta'),
            'estado' => $this->matcher->regex('Procesada', '^(Pendiente|Procesada|Cancelada)$'),
            'monto_total' => $this->matcher->decimalV3(30000.00),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/inventario/salidas');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('salidas inventario exist')
            ->uponReceiving('a request to list salidas de inventario')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($salidaShape)));

        $response = $this->callMockServer('GET', '/api/inventario/salidas?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testCreateEntrada(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/inventario/entradas');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody([
            'almacen_id' => 1,
            'fecha_entrada' => '2026-03-22',
            'tipo_entrada' => 'Compra',
            'documento_referencia' => 'OC-002',
            'observaciones' => 'Entrada de mercadería',
            'detalles' => [
                [
                    'producto_id' => 1,
                    'cantidad' => 20,
                    'precio_unitario' => 3000.00,
                ],
            ],
        ]);

        $responseBody = [
            'success' => $this->matcher->boolean(true),
            'message' => $this->matcher->like('Recurso creado exitosamente'),
            'data' => [
                'id' => $this->matcher->integerV3(1),
                'estado' => $this->matcher->like('Pendiente'),
                'monto_total' => $this->matcher->decimalV3(60000.00),
                'creado_en' => $this->iso8601(),
            ],
        ];

        $this->builder
            ->given('almacen and productos exist')
            ->uponReceiving('a request to create an entrada de inventario')
            ->with($request)
            ->willRespondWith($this->jsonResponse(201, $responseBody));

        $response = $this->callMockServer('POST', '/api/inventario/entradas', [
            'almacen_id' => 1,
            'fecha_entrada' => '2026-03-22',
            'tipo_entrada' => 'Compra',
            'documento_referencia' => 'OC-002',
            'observaciones' => 'Entrada de mercadería',
            'detalles' => [
                ['producto_id' => 1, 'cantidad' => 20, 'precio_unitario' => 3000.00],
            ],
        ]);
        $this->assertSame(201, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
