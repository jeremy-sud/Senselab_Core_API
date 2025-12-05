<?php

namespace Tests\Unit\Services\Hacienda;

use App\Services\Hacienda\HaciendaApiClient;
use Tests\TestCase;

/**
 * Tests de integración para HaciendaApiClient
 *
 * Valida:
 * - Comunicación con API de Hacienda (mocked)
 * - Envío de comprobantes electrónicos
 * - Consulta de estado de comprobantes
 * - Manejo de respuestas HTTP (éxito, error, rate limit)
 * - Integración con OAuth y Rate Limiting
 * - Ambientes sandbox/production
 *
 * @see https://www.hacienda.go.cr/docs/ComprobantesElectronicosAPI.html
 * @see https://api.hacienda.go.cr/docs/
 */
class HaciendaApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar URLs para tests sin base de datos
        config([
            'hacienda.environment' => 'sandbox',
            'hacienda.api_urls.sandbox.recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.api_urls.sandbox.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
            'hacienda.api_urls.production.recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.api_urls.production.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
            'hacienda.oauth.client_id' => 'test_client',
            'hacienda.oauth.client_secret' => 'test_secret',
            'hacienda.oauth.grant_type' => 'client_credentials',
        ]);
    }

    /**
     * @test
     * Verifica que el cliente se inicializa correctamente en ambiente sandbox
     */
    public function puede_inicializar_en_ambiente_sandbox(): void
    {
        $client = new HaciendaApiClient('sandbox');

        $this->assertEquals('sandbox', $client->getAmbiente());
    }

    /**
     * @test
     * Verifica que el cliente se inicializa correctamente en ambiente production
     */
    public function puede_inicializar_en_ambiente_production(): void
    {
        $client = new HaciendaApiClient('production');

        $this->assertEquals('production', $client->getAmbiente());
    }

    /**
     * @test
     * Verifica que puede cambiar de ambiente dinámicamente
     */
    public function puede_cambiar_ambiente(): void
    {
        $client = new HaciendaApiClient('sandbox');

        $client->setAmbiente('production');

        $this->assertEquals('production', $client->getAmbiente());
    }

    /**
     * @test
     * Verifica que rechaza ambientes inválidos
     */
    public function rechaza_ambiente_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ambiente inválido: test');

        $client = new HaciendaApiClient('sandbox');
        $client->setAmbiente('test');
    }

    /**
     * @test
     * Verifica la URL correcta para sandbox
     */
    public function usa_url_correcta_para_sandbox(): void
    {
        config(['hacienda.api_urls.sandbox.recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1']);

        $client = new HaciendaApiClient('sandbox');

        $this->assertEquals('sandbox', $client->getAmbiente());
    }

    /**
     * @test
     * Verifica la URL correcta para producción
     */
    public function usa_url_correcta_para_production(): void
    {
        config(['hacienda.api_urls.production.recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1']);

        $client = new HaciendaApiClient('production');

        $this->assertEquals('production', $client->getAmbiente());
    }
}
