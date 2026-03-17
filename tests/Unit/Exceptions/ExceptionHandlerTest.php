<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\HaciendaException;
use App\Exceptions\InventarioException;
use App\Exceptions\VentaException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para el Exception Handler centralizado — FASE 15
 *
 * Verifica que las DomainException se rendericen como JSON
 * con el envelope unificado y HTTP status correcto.
 */
class ExceptionHandlerTest extends TestCase
{
    #[Test]
    public function domain_exception_se_renderiza_como_json(): void
    {
        // Registrar una ruta temporal que lance la excepción
        \Illuminate\Support\Facades\Route::get('/_test/exception', function () {
            throw InventarioException::entradaYaProcesada();
        });

        $response = $this->getJson('/_test/exception');

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
            'code',
            'trace_id',
        ]);
        $response->assertJson([
            'success' => false,
            'code' => 422,
        ]);
        $response->assertJsonFragment([
            'message' => 'La entrada ya fue procesada anteriormente',
        ]);
    }

    #[Test]
    public function domain_exception_respeta_http_status_code(): void
    {
        \Illuminate\Support\Facades\Route::get('/_test/exception-502', function () {
            throw HaciendaException::apiCommunicationError('timeout', 3);
        });

        $response = $this->getJson('/_test/exception-502');

        $response->assertStatus(502);
        $response->assertJson([
            'success' => false,
            'code' => 502,
        ]);
    }

    #[Test]
    public function domain_exception_409_conflict(): void
    {
        \Illuminate\Support\Facades\Route::get('/_test/exception-409', function () {
            throw VentaException::ventaYaFacturada(15);
        });

        $response = $this->getJson('/_test/exception-409');

        $response->assertStatus(409);
        $response->assertJson(['success' => false, 'code' => 409]);
    }

    #[Test]
    public function domain_exception_incluye_trace_id_en_body(): void
    {
        $knownTraceId = 'test-trace-id-12345';

        \Illuminate\Support\Facades\Route::get('/_test/exception-trace', function () {
            throw InventarioException::stockInsuficiente(1);
        });

        $response = $this->withHeaders(['X-Trace-ID' => $knownTraceId])
            ->getJson('/_test/exception-trace');

        $response->assertStatus(422);
        $body = $response->json();
        $this->assertSame($knownTraceId, $body['trace_id']);
    }

    #[Test]
    public function domain_exception_incluye_debug_info_en_modo_debug(): void
    {
        config(['app.debug' => true]);

        \Illuminate\Support\Facades\Route::get('/_test/exception-debug', function () {
            throw InventarioException::salidaYaProcesada();
        });

        $response = $this->getJson('/_test/exception-debug');

        $response->assertStatus(422);
        $body = $response->json();
        $this->assertArrayHasKey('exception', $body);
        $this->assertSame(InventarioException::class, $body['exception']);
        $this->assertArrayHasKey('file', $body);
        $this->assertArrayHasKey('line', $body);
    }
}
