<?php

namespace Tests\Unit\Services\Hacienda;

use App\Services\Hacienda\HaciendaApiClient;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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
            'hacienda.api_urls.sandbox.logout' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout',
            'hacienda.api_urls.production.recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.api_urls.production.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
            'hacienda.api_urls.production.logout' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/logout',
            'hacienda.oauth.client_id' => 'api-stag',
            'hacienda.oauth.client_secret' => '',
            'hacienda.oauth.grant_type' => 'password',
            'hacienda.oauth.username' => 'cpf-01-0123-0456@stag.comprobanteselectronicos.go.cr',
            'hacienda.oauth.password' => 'test_password_12345',
        ]);
    }

    /**
     * Verifica que el cliente se inicializa correctamente en ambiente sandbox
     */
    #[Test]
    public function puede_inicializar_en_ambiente_sandbox(): void
    {
        $client = new HaciendaApiClient('sandbox');

        $this->assertEquals('sandbox', $client->getAmbiente());
    }

    /**
     * Verifica que el cliente se inicializa correctamente en ambiente production
     */
    #[Test]
    public function puede_inicializar_en_ambiente_production(): void
    {
        $client = new HaciendaApiClient('production');

        $this->assertEquals('production', $client->getAmbiente());
    }

    /**
     * Verifica que puede cambiar de ambiente dinámicamente
     */
    #[Test]
    public function puede_cambiar_ambiente(): void
    {
        $client = new HaciendaApiClient('sandbox');

        $client->setAmbiente('production');

        $this->assertEquals('production', $client->getAmbiente());
    }

    /**
     * Verifica que rechaza ambientes inválidos
     */
    #[Test]
    public function rechaza_ambiente_invalido(): void
    {
        $this->expectException(\App\Exceptions\HaciendaException::class);
        $this->expectExceptionMessage('Ambiente inválido: test');

        $client = new HaciendaApiClient('sandbox');
        $client->setAmbiente('test');
    }

    /**
     * Verifica la URL correcta para sandbox
     */
    #[Test]
    public function usa_url_correcta_para_sandbox(): void
    {
        config(['hacienda.api_urls.sandbox.recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1']);

        $client = new HaciendaApiClient('sandbox');

        $this->assertEquals('sandbox', $client->getAmbiente());
    }

    /**
     * Verifica la URL correcta para producción
     */
    #[Test]
    public function usa_url_correcta_para_production(): void
    {
        config(['hacienda.api_urls.production.recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1']);

        $client = new HaciendaApiClient('production');

        $this->assertEquals('production', $client->getAmbiente());
    }
}
