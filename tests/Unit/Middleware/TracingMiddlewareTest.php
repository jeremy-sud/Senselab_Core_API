<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TracingMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para TracingMiddleware
 *
 * FASE 22 - Escalabilidad
 */
#[CoversClass(TracingMiddleware::class)]
class TracingMiddlewareTest extends TestCase
{
    private TracingMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TracingMiddleware();
    }

    #[Test]
    public function genera_trace_id_cuando_no_existe(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('X-Trace-Id'));
        $traceId = $result->headers->get('X-Trace-Id');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $traceId);
    }

    #[Test]
    public function propaga_trace_id_del_request(): void
    {
        $existingTraceId = 'abcd1234abcd1234abcd1234abcd1234';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Trace-Id', $existingTraceId);
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertEquals($existingTraceId, $result->headers->get('X-Trace-Id'));
    }

    #[Test]
    public function genera_span_id_unico(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('X-Span-Id'));
        $spanId = $result->headers->get('X-Span-Id');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $spanId);
    }

    #[Test]
    public function agrega_response_time_header(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('X-Response-Time'));
        $responseTime = $result->headers->get('X-Response-Time');
        $this->assertMatchesRegularExpression('/^\d+\.\d+ms$/', $responseTime);
    }

    #[Test]
    public function almacena_trace_id_en_request_attributes(): void
    {
        $request = Request::create('/api/test', 'GET');
        $response = new JsonResponse(['ok' => true]);

        $this->middleware->handle($request, function ($req) use ($response) {
            $this->assertNotNull($req->attributes->get('trace_id'));
            $this->assertNotNull($req->attributes->get('span_id'));
            return $response;
        });
    }

    #[Test]
    public function propaga_parent_span_id_cuando_existe(): void
    {
        $parentSpanId = 'abcd1234abcd1234';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Span-Id', $parentSpanId);
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('X-Parent-Span-Id'));
        $this->assertEquals($parentSpanId, $result->headers->get('X-Parent-Span-Id'));
    }

    #[Test]
    public function span_id_es_diferente_de_parent_span_id(): void
    {
        $parentSpanId = 'abcd1234abcd1234';

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-Span-Id', $parentSpanId);
        $response = new JsonResponse(['ok' => true]);

        $result = $this->middleware->handle($request, fn () => $response);

        $newSpanId = $result->headers->get('X-Span-Id');
        $this->assertNotEquals($parentSpanId, $newSpanId);
    }

    #[Test]
    public function funciona_con_post_request(): void
    {
        $request = Request::create('/api/test', 'POST', ['data' => 'test']);
        $response = new JsonResponse(['created' => true], 201);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('X-Trace-Id'));
        $this->assertTrue($result->headers->has('X-Span-Id'));
        $this->assertEquals(201, $result->getStatusCode());
    }
}
