<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeliverWebhookJob;
use App\Models\Empresa;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeliverWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = $this->createEmpresa();
        $this->webhook = Webhook::factory()->for($this->empresa)->create([
            'secret' => Str::random(64),
            'eventos' => ['venta.creada'],
            'activo' => true,
        ]);
    }

    public function test_firma_hmac_sha256_correcta()
    {
        $payload = ['foo' => 'bar'];
        $job = new DeliverWebhookJob($this->webhook->id, 'venta.creada', $payload, $this->empresa->id);
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('generateSignature');
        $method->setAccessible(true);
        $payloadJson = json_encode($payload);
        $signature = $method->invoke($job, $payloadJson, $this->webhook->secret);
        $this->assertEquals('sha256=' . hash_hmac('sha256', $payloadJson, $this->webhook->secret), $signature);
    }

    public function test_no_envia_si_webhook_inactivo()
    {
        $this->webhook->update(['activo' => false]);
        Http::fake();
        $job = new DeliverWebhookJob($this->webhook->id, 'venta.creada', ['foo' => 'bar'], $this->empresa->id);
        $job->handle();
        Http::assertNothingSent();
    }

    // ...más tests: retry, backoff, log, manejo de error, etc.
}
