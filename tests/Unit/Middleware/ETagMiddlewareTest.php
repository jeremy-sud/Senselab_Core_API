<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ETagMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para ETagMiddleware
 *
 * FASE 22 - Escalabilidad
 */
#[CoversClass(ETagMiddleware::class)]
class ETagMiddlewareTest extends TestCase
{
    private ETagMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ETagMiddleware();
    }

    #[Test]
    public function agrega_header_etag_a_respuesta_get(): void
    {
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['data' => ['id' => 1, 'nombre' => 'Producto Test']]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('ETag'));
        $this->assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/', $result->headers->get('ETag'));
    }

    #[Test]
    public function responde_304_cuando_etag_coincide(): void
    {
        $content = json_encode(['data' => ['id' => 1]]);
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['data' => ['id' => 1]]);

        // Primera llamada para obtener el ETag
        $result1 = $this->middleware->handle($request, fn () => $response);
        $etag = $result1->headers->get('ETag');

        // Segunda llamada con If-None-Match
        $request2 = Request::create('/api/productos', 'GET');
        $request2->headers->set('If-None-Match', $etag);

        $result2 = $this->middleware->handle($request2, fn () => $response);

        $this->assertEquals(304, $result2->getStatusCode());
        $this->assertEquals('', $result2->getContent());
    }

    #[Test]
    public function no_aplica_a_metodo_post(): void
    {
        $request = Request::create('/api/productos', 'POST');
        $response = new JsonResponse(['data' => ['id' => 1]], 201);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertFalse($result->headers->has('ETag'));
        $this->assertEquals(201, $result->getStatusCode());
    }

    #[Test]
    public function no_aplica_a_rutas_excluidas(): void
    {
        $request = Request::create('/api/health', 'GET');
        $response = new JsonResponse(['status' => 'ok']);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertFalse($result->headers->has('ETag'));
    }

    #[Test]
    public function soporta_multiples_etags_en_if_none_match(): void
    {
        $content = json_encode(['data' => ['id' => 1]]);
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['data' => ['id' => 1]]);

        // Obtener el ETag correcto
        $result1 = $this->middleware->handle($request, fn () => $response);
        $etag = $result1->headers->get('ETag');

        // Enviar múltiples ETags (uno correcto)
        $request2 = Request::create('/api/productos', 'GET');
        $request2->headers->set('If-None-Match', '"invalidetag123", ' . $etag . ', "otheretag456"');

        $result2 = $this->middleware->handle($request2, fn () => $response);

        $this->assertEquals(304, $result2->getStatusCode());
    }

    #[Test]
    public function agrega_cache_control_private(): void
    {
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['data' => []]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('Cache-Control'));
        $this->assertStringContainsString('private', $result->headers->get('Cache-Control'));
    }

    #[Test]
    public function no_aplica_a_respuestas_de_error(): void
    {
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['error' => 'Not found'], 404);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertFalse($result->headers->has('ETag'));
    }

    #[Test]
    public function soporta_weak_etags(): void
    {
        $content = json_encode(['data' => ['id' => 1]]);
        $request = Request::create('/api/productos', 'GET');
        $response = new JsonResponse(['data' => ['id' => 1]]);

        // Obtener el ETag correcto
        $result1 = $this->middleware->handle($request, fn () => $response);
        $etag = $result1->headers->get('ETag');

        // Enviar como weak ETag
        $request2 = Request::create('/api/productos', 'GET');
        $request2->headers->set('If-None-Match', 'W/' . $etag);

        $result2 = $this->middleware->handle($request2, fn () => $response);

        $this->assertEquals(304, $result2->getStatusCode());
    }
}
