<?php

namespace Tests\Unit\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Empresa;
use App\Models\Webhook;
use App\Services\WebhookDispatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDispatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected WebhookDispatcherService $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = $this->createEmpresa();
        $this->dispatcher = app(WebhookDispatcherService::class);
    }

    public function test_despacha_a_webhooks_que_escuchan_evento()
    {
        Queue::fake();
        $webhook = Webhook::factory()->for($this->empresa)->create([
            'eventos' => ['venta.creada'],
            'activo' => true,
        ]);
        $payload = ['foo' => 'bar'];
        $this->dispatcher->despachar('venta.creada', $this->empresa->id, $payload);
        Queue::assertPushed(DeliverWebhookJob::class, function ($job) use ($webhook) {
            return $job->webhookId === $webhook->id && $job->evento === 'venta.creada';
        });
    }

    public function test_no_despacha_a_webhooks_inactivos()
    {
        Queue::fake();
        $webhook = Webhook::factory()->for($this->empresa)->inactivo()->create([
            'eventos' => ['venta.creada'],
        ]);
        $this->dispatcher->despachar('venta.creada', $this->empresa->id, []);
        Queue::assertNotPushed(DeliverWebhookJob::class);
    }

    // ...más tests: no despacha si no escucha evento, multiwebhook, etc.
}
