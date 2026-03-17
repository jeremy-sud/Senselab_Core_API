<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\AIServiceException;
use App\Exceptions\CompraException;
use App\Exceptions\ContabilidadException;
use App\Exceptions\DomainException;
use App\Exceptions\FacturacionElectronicaException;
use App\Exceptions\HaciendaException;
use App\Exceptions\InventarioException;
use App\Exceptions\MultiTenancyException;
use App\Exceptions\NominaException;
use App\Exceptions\VentaException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests para las excepciones de dominio — FASE 15
 *
 * Verifica que cada factory method retorne mensaje y HTTP status correcto,
 * y que todas extienden DomainException.
 */
class DomainExceptionTest extends TestCase
{
    // ─── Jerarquía ───────────────────────────────────────────────

    #[Test]
    #[DataProvider('allExceptionClassesProvider')]
    public function todas_las_excepciones_extienden_domain_exception(string $class): void
    {
        $this->assertTrue(
            is_subclass_of($class, DomainException::class),
            "{$class} no extiende DomainException"
        );
    }

    public static function allExceptionClassesProvider(): array
    {
        return [
            'HaciendaException' => [HaciendaException::class],
            'InventarioException' => [InventarioException::class],
            'VentaException' => [VentaException::class],
            'ContabilidadException' => [ContabilidadException::class],
            'CompraException' => [CompraException::class],
            'NominaException' => [NominaException::class],
            'MultiTenancyException' => [MultiTenancyException::class],
            'FacturacionElectronicaException' => [FacturacionElectronicaException::class],
            'AIServiceException' => [AIServiceException::class],
        ];
    }

    // ─── InventarioException ─────────────────────────────────────

    #[Test]
    public function inventario_entrada_ya_procesada(): void
    {
        $e = InventarioException::entradaYaProcesada();
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('entrada ya fue procesada', $e->getMessage());
    }

    #[Test]
    public function inventario_entrada_sin_productos(): void
    {
        $e = InventarioException::entradaSinProductos();
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('sin productos', $e->getMessage());
    }

    #[Test]
    public function inventario_salida_ya_procesada(): void
    {
        $e = InventarioException::salidaYaProcesada();
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('salida ya fue procesada', $e->getMessage());
    }

    #[Test]
    public function inventario_stock_insuficiente(): void
    {
        $e = InventarioException::stockInsuficiente(42);
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    // ─── VentaException ──────────────────────────────────────────

    #[Test]
    public function venta_estado_invalido(): void
    {
        $e = VentaException::estadoInvalido('borrador');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('borrador', $e->getMessage());
    }

    #[Test]
    public function venta_ya_facturada(): void
    {
        $e = VentaException::ventaYaFacturada(99);
        $this->assertSame(409, $e->getHttpStatusCode());
        $this->assertStringContainsString('#99', $e->getMessage());
    }

    #[Test]
    public function venta_ya_anulada(): void
    {
        $e = VentaException::ventaYaAnulada();
        $this->assertSame(409, $e->getHttpStatusCode());
    }

    // ─── ContabilidadException ───────────────────────────────────

    #[Test]
    public function contabilidad_asiento_desbalanceado(): void
    {
        $e = ContabilidadException::asientoDesbalanceado(1000.50, 999.00);
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('1000.5', $e->getMessage());
        $this->assertStringContainsString('999', $e->getMessage());
    }

    #[Test]
    public function contabilidad_periodo_cerrado(): void
    {
        $e = ContabilidadException::periodoContableCerrado('2025-01');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('2025-01', $e->getMessage());
    }

    #[Test]
    public function contabilidad_cuenta_inactiva(): void
    {
        $e = ContabilidadException::cuentaContableInactiva('1-01-001');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('1-01-001', $e->getMessage());
    }

    // ─── CompraException ─────────────────────────────────────────

    #[Test]
    public function compra_orden_ya_aprobada(): void
    {
        $e = CompraException::ordenYaAprobada(5);
        $this->assertSame(409, $e->getHttpStatusCode());
        $this->assertStringContainsString('#5', $e->getMessage());
    }

    #[Test]
    public function compra_proveedor_inactivo(): void
    {
        $e = CompraException::proveedorInactivo(10);
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('#10', $e->getMessage());
    }

    // ─── NominaException ─────────────────────────────────────────

    #[Test]
    public function nomina_periodo_ya_cerrado(): void
    {
        $e = NominaException::periodoYaCerrado('2025-06');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('2025-06', $e->getMessage());
    }

    #[Test]
    public function nomina_pago_ya_procesado(): void
    {
        $e = NominaException::pagoYaProcesado(77);
        $this->assertSame(409, $e->getHttpStatusCode());
        $this->assertStringContainsString('#77', $e->getMessage());
    }

    #[Test]
    public function nomina_planilla_ya_generada(): void
    {
        $e = NominaException::planillaYaGenerada('2025-06');
        $this->assertSame(409, $e->getHttpStatusCode());
        $this->assertStringContainsString('2025-06', $e->getMessage());
    }

    // ─── MultiTenancyException ───────────────────────────────────

    #[Test]
    public function multi_tenancy_empresa_no_encontrada(): void
    {
        $e = MultiTenancyException::empresaNoEncontrada();
        $this->assertSame(403, $e->getHttpStatusCode());
    }

    #[Test]
    public function multi_tenancy_sin_acceso(): void
    {
        $e = MultiTenancyException::sinAccesoAEmpresa(3);
        $this->assertSame(403, $e->getHttpStatusCode());
        $this->assertStringContainsString('#3', $e->getMessage());
    }

    #[Test]
    public function multi_tenancy_empresa_inactiva(): void
    {
        $e = MultiTenancyException::empresaInactiva(3);
        $this->assertSame(403, $e->getHttpStatusCode());
    }

    // ─── FacturacionElectronicaException ─────────────────────────

    #[Test]
    public function fe_comprobante_ya_anulado(): void
    {
        $e = FacturacionElectronicaException::comprobanteYaAnulado('50601011800012345678');
        $this->assertSame(409, $e->getHttpStatusCode());
        $this->assertStringContainsString('50601011800012345678', $e->getMessage());
    }

    #[Test]
    public function fe_consecutivo_agotado(): void
    {
        $e = FacturacionElectronicaException::consecutivoAgotado('FE');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('FE', $e->getMessage());
    }

    #[Test]
    public function fe_sin_certificado_activo(): void
    {
        $e = FacturacionElectronicaException::sinCertificadoActivo();
        $this->assertSame(422, $e->getHttpStatusCode());
    }

    // ─── AIServiceException ──────────────────────────────────────

    #[Test]
    public function ai_api_error(): void
    {
        $previous = new \RuntimeException('timeout');
        $e = AIServiceException::apiError('OpenAI', 'rate limit', $previous);
        $this->assertSame(502, $e->getHttpStatusCode());
        $this->assertStringContainsString('OpenAI', $e->getMessage());
        $this->assertStringContainsString('rate limit', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
    }

    #[Test]
    public function ai_cuota_excedida(): void
    {
        $e = AIServiceException::cuotaExcedida('Gemini');
        $this->assertSame(429, $e->getHttpStatusCode());
        $this->assertStringContainsString('Gemini', $e->getMessage());
    }

    #[Test]
    public function ai_configuration_error(): void
    {
        $e = AIServiceException::configurationError('OpenAI', 'API key missing');
        $this->assertSame(500, $e->getHttpStatusCode());
    }

    // ─── HaciendaException (muestras representativas) ────────────

    #[Test]
    public function hacienda_api_communication_error(): void
    {
        $e = HaciendaException::apiCommunicationError('timeout', 3);
        $this->assertSame(502, $e->getHttpStatusCode());
        $this->assertStringContainsString('3 intentos', $e->getMessage());
    }

    #[Test]
    public function hacienda_rate_limit_exceeded(): void
    {
        $e = HaciendaException::rateLimitExceeded(60);
        $this->assertSame(429, $e->getHttpStatusCode());
        $this->assertStringContainsString('60', $e->getMessage());
    }

    #[Test]
    public function hacienda_oauth_config_error(): void
    {
        $e = HaciendaException::oauthConfigError('Missing client_id');
        $this->assertSame(500, $e->getHttpStatusCode());
    }

    #[Test]
    public function hacienda_certificado_no_encontrado(): void
    {
        $e = HaciendaException::certificadoNoEncontrado(7);
        $this->assertSame(404, $e->getHttpStatusCode());
        $this->assertStringContainsString('7', $e->getMessage());
    }

    #[Test]
    public function hacienda_certificado_vencido(): void
    {
        $e = HaciendaException::certificadoVencido('2024-12-31');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('2024-12-31', $e->getMessage());
    }

    #[Test]
    public function hacienda_xml_parse_error(): void
    {
        $e = HaciendaException::xmlParseError();
        $this->assertSame(422, $e->getHttpStatusCode());
    }

    #[Test]
    public function hacienda_firma_error(): void
    {
        $e = HaciendaException::firmaError('algo salió mal');
        $this->assertSame(500, $e->getHttpStatusCode());
        $this->assertStringContainsString('algo salió mal', $e->getMessage());
    }

    #[Test]
    public function hacienda_pkcs12_error(): void
    {
        $e = HaciendaException::pkcs12Error();
        $this->assertSame(500, $e->getHttpStatusCode());
    }

    #[Test]
    public function hacienda_invalid_ambiente(): void
    {
        $e = HaciendaException::invalidAmbiente('produccion');
        $this->assertSame(422, $e->getHttpStatusCode());
        $this->assertStringContainsString('produccion', $e->getMessage());
    }

    // ─── Previous exception chaining ────────────────────────────

    #[Test]
    public function exception_chaining_works(): void
    {
        $cause = new \RuntimeException('root cause');
        $e = HaciendaException::networkError('connection refused', $cause);
        $this->assertSame($cause, $e->getPrevious());
        $this->assertInstanceOf(DomainException::class, $e);
    }

    // ─── Default HTTP status code ────────────────────────────────

    #[Test]
    public function default_http_status_is_422(): void
    {
        // Use a concrete exception with default status to verify base class behavior
        $e = InventarioException::entradaYaProcesada();
        $this->assertSame(422, $e->getHttpStatusCode());
    }
}
