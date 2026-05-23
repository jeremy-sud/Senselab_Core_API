<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SunsetMonitorMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para SunsetMonitorMiddleware
 */
#[CoversClass(SunsetMonitorMiddleware::class)]
class SunsetMonitorMiddlewareTest extends TestCase
{
    private SunsetMonitorMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SunsetMonitorMiddleware();
    }

    #[Test]
    public function injects_deprecation_and_sunset_headers_on_v4_routes(): void
    {
        $request = Request::create('/api/v4/users', 'GET');
        $response = new JsonResponse(['data' => []]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('Deprecation'));
        $this->assertTrue($result->headers->has('Sunset'));
        $this->assertEquals('2026-01-01', $result->headers->get('Deprecation'));
        $this->assertEquals('2026-12-31', $result->headers->get('Sunset'));
    }

    #[Test]
    public function does_not_inject_deprecation_and_sunset_headers_on_other_routes(): void
    {
        $request = Request::create('/api/v5/users', 'GET');
        $response = new JsonResponse(['data' => []]);

        $result = $this->middleware->handle($request, fn () => $response);

        $this->assertFalse($result->headers->has('Deprecation'));
        $this->assertFalse($result->headers->has('Sunset'));
    }
}
