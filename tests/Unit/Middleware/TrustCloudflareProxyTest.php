<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TrustCloudflareProxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(TrustCloudflareProxy::class)]
class TrustCloudflareProxyTest extends TestCase
{
    private TrustCloudflareProxy $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TrustCloudflareProxy();
    }

    #[Test]
    public function extracts_valid_client_ip_from_cf_connecting_ip_header(): void
    {
        $request = Request::create('/api/health/live', 'GET');
        $request->headers->set('CF-Connecting-IP', '201.200.55.99');

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('201.200.55.99', $req->server->get('REMOTE_ADDR'));
            return new JsonResponse(['ok' => true]);
        });
    }

    #[Test]
    public function ignores_invalid_cf_connecting_ip_values(): void
    {
        $request = Request::create('/api/health/live', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->headers->set('CF-Connecting-IP', 'invalid-ip-string');

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('127.0.0.1', $req->server->get('REMOTE_ADDR'));
            return new JsonResponse(['ok' => true]);
        });
    }

    #[Test]
    public function sets_https_when_cf_visitor_declares_https(): void
    {
        $request = Request::create('http://api.scisenselab.com/api/health', 'GET');
        $request->headers->set('CF-Visitor', '{"scheme":"https"}');

        $this->middleware->handle($request, function ($req) {
            $this->assertEquals('on', $req->server->get('HTTPS'));
            $this->assertEquals(443, $req->server->get('SERVER_PORT'));
            return new JsonResponse(['ok' => true]);
        });
    }
}
