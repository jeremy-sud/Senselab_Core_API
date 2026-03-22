<?php

namespace Tests\Contract\Consumer;

use Tests\Contract\PactTestCase;

/**
 * Contract tests for the Comprobantes Electrónicos (Facturación Electrónica) API.
 *
 * Covers list, show, and generate operations for Costa Rica electronic invoices.
 */
class ComprobanteFeApiTest extends PactTestCase
{
    public function testGetComprobantesList(): void
    {
        $comprobanteShape = [
            'id' => $this->matcher->integerV3(1),
            'numero_consecutivo' => $this->matcher->like('00100001010000000001'),
            'clave' => $this->matcher->like('50601032600310167890600100001010000000001199999999'),
            'fecha_emision' => $this->iso8601(),
            'receptor_nombre' => $this->matcher->like('Cliente S.A.'),
            'receptor_identificacion' => $this->matcher->like('3101678906'),
            'moneda' => $this->matcher->regex('CRC', '^(CRC|USD)$'),
            'total_comprobante' => $this->matcher->decimalV3(11300.00),
            'estado' => $this->matcher->regex('aceptado', '^(pendiente|aceptado|rechazado)$'),
        ];

        $request = $this->authenticatedRequest('GET', '/api/comprobantes');
        $request->addQueryParameter('per_page', '15');

        $this->builder
            ->given('comprobantes electronicos exist')
            ->uponReceiving('a request to list comprobantes electronicos')
            ->with($request)
            ->willRespondWith($this->jsonResponse(200, $this->paginatedEnvelope($comprobanteShape)));

        $response = $this->callMockServer('GET', '/api/comprobantes?per_page=15');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGetComprobanteById(): void
    {
        $comprobanteDetail = $this->successEnvelope([
            'id' => $this->matcher->integerV3(1),
            'numero_consecutivo' => $this->matcher->like('00100001010000000001'),
            'clave' => $this->matcher->like('50601032600310167890600100001010000000001199999999'),
            'fecha_emision' => $this->iso8601(),
            'receptor_tipo_identificacion' => $this->matcher->regex('02', '^0[1-4]$'),
            'receptor_identificacion' => $this->matcher->like('3101678906'),
            'receptor_nombre' => $this->matcher->like('Cliente S.A.'),
            'receptor_correo' => $this->matcher->email(),
            'moneda' => $this->matcher->regex('CRC', '^(CRC|USD)$'),
            'tipo_cambio' => $this->matcher->decimalV3(1.00),
            'total_gravado' => $this->matcher->decimalV3(10000.00),
            'total_exento' => $this->matcher->decimalV3(0.00),
            'total_venta' => $this->matcher->decimalV3(10000.00),
            'total_descuentos' => $this->matcher->decimalV3(0.00),
            'total_impuesto' => $this->matcher->decimalV3(1300.00),
            'total_comprobante' => $this->matcher->decimalV3(11300.00),
            'estado' => $this->matcher->regex('aceptado', '^(pendiente|aceptado|rechazado)$'),
            'estado_hacienda' => $this->matcher->like('aceptado'),
        ]);

        $this->builder
            ->given('comprobante with id 1 exists')
            ->uponReceiving('a request to get comprobante detail by id')
            ->with($this->authenticatedRequest('GET', '/api/comprobantes/1'))
            ->willRespondWith($this->jsonResponse(200, $comprobanteDetail));

        $response = $this->callMockServer('GET', '/api/comprobantes/1');
        $this->assertSame(200, $response['status']);
        $this->assertTrue($this->builder->verify());
    }

    public function testGenerateComprobanteForHacienda(): void
    {
        $request = $this->authenticatedRequest('POST', '/api/v1/hacienda/generar');
        $request->addHeader('Content-Type', 'application/json');
        $request->setBody([
            'comprobante_id' => 1,
        ]);

        $responseBody = [
            'success' => $this->matcher->boolean(true),
            'data' => [
                'id' => $this->matcher->integerV3(1),
                'clave' => $this->matcher->like('50601032600310167890600100001010000000001199999999'),
                'estado' => $this->matcher->like('generado'),
                'tipo' => $this->matcher->like('factura_electronica'),
            ],
        ];

        $this->builder
            ->given('comprobante 1 is ready for hacienda')
            ->uponReceiving('a request to generate comprobante for hacienda')
            ->with($request)
            ->willRespondWith($this->jsonResponse(201, $responseBody));

        $response = $this->callMockServer('POST', '/api/v1/hacienda/generar', ['comprobante_id' => 1]);
        $this->assertSame(201, $response['status']);
        $this->assertTrue($this->builder->verify());
    }
}
