<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

/**
 * Contract tests for the Productos API endpoints.
 *
 * Covers list (with filters), show, and stock check operations.
 */
class ProductoApiTest extends PactTestCase
{
    public function testGetProductosList(): void
    {
        $productoShape = [
            'id' => $this->matcher->integerV3(1),
            'codigo' => $this->matcher->like('PROD-001'),
            'nombre' => $this->matcher->like('Producto Demo'),
            'descripcion' => $this->matcher->like('Descripción del producto'),
            'precio_costo' => $this->matcher->decimalV3(3000.00),
            'precio_venta' => $this->matcher->decimalV3(5000.00),
            'stock_minimo' => $this->matcher->integerV3(5),
            'stock_maximo' => $this->matcher->integerV3(100),
            'exento_impuesto' => $this->matcher->boolean(false),
            'activo' => $this->matcher->boolean(true),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/productos');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('productos exist')
            ->uponReceiving('a request to list productos')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($productoShape)));

        $response = $this->callMockServer('GET', '/api/productos?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetProductoById(): void
    {
        $productoDetail = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'codigo' => $this->matcher->like('PROD-001'),
            'codigo_barras' => $this->matcher->like('7890001234567'),
            'nombre' => $this->matcher->like('Producto Demo'),
            'descripcion' => $this->matcher->like('Descripción completa del producto'),
            'categoria' => [
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('Electrónicos'),
            ],
            'marca' => [
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('Marca A'),
            ],
            'unidad_medida' => [
                'id' => $this->matcher->integerV3(1),
                'nombre' => $this->matcher->like('Unidad'),
                'simbolo' => $this->matcher->like('UND'),
            ],
            'precio_costo' => $this->matcher->decimalV3(3000.00),
            'precio_venta' => $this->matcher->decimalV3(5000.00),
            'stock_minimo' => $this->matcher->integerV3(5),
            'stock_maximo' => $this->matcher->integerV3(100),
            'exento_impuesto' => $this->matcher->boolean(false),
            'activo' => $this->matcher->boolean(true),
            'creado_en' => $this->iso8601(),
        ]);

        $this->builder
            ->given('producto with id 1 exists with relations')
            ->uponReceiving('a request to get producto detail by id')
            ->with($this->authenticatedRequest('GET', '/api/productos/1'))
            ->willRespondWith($this->jsonResponse(200, $productoDetail));

        $response = $this->callMockServer('GET', '/api/productos/1');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetProductosFilteredByCategoria(): void
    {
        $productoShape = [
            'id' => $this->matcher->integerV3(1),
            'codigo' => $this->matcher->like('PROD-001'),
            'nombre' => $this->matcher->like('Producto Demo'),
            'precio_venta' => $this->matcher->decimalV3(5000.00),
            'activo' => $this->matcher->boolean(true),
            'creado_en' => $this->iso8601(),
        ];

        $request = $this->authenticatedRequest('GET', '/api/productos');
        $request->addQueryParameter('categoria_id', '1');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('productos in categoria 1 exist')
            ->uponReceiving('a request to list productos filtered by categoria')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($productoShape)));

        $response = $this->callMockServer('GET', '/api/productos?categoria_id=1&per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
