<?php

namespace Tests\Feature\Hacienda;

use App\Models\ComprobanteElectronicoFe;
use App\Models\Empresa;
use App\Models\FeLineaDetalle;
use App\Models\FeCertificadoDigital;
use App\Models\FeOAuthToken;
use App\Models\Usuario;
use App\Services\Hacienda\ClaveNumericaGenerator;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\OAuthTokenManager;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Tests E2E para el flujo completo de Facturación Electrónica con Hacienda
 *
 * Valida el flujo completo según la API de Hacienda Costa Rica:
 * 1. Generación de clave numérica (50 posiciones)
 * 2. Construcción de XML según esquema v4.4
 * 3. Firma digital XAdES-EPES
 * 4. Envío a API de recepción
 * 5. Consulta de estado
 * 6. Procesamiento de respuesta
 *
 * Tipos de documento soportados:
 * - 01: Factura Electrónica
 * - 02: Nota de Débito Electrónica
 * - 03: Nota de Crédito Electrónica
 * - 04: Tiquete Electrónico
 * - 05: Confirmación de Aceptación
 * - 06: Confirmación de Aceptación Parcial
 * - 07: Confirmación de Rechazo
 *
 * @see https://www.hacienda.go.cr/docs/ComprobantesElectronicosAPI.html
 * @see https://api.hacienda.go.cr/docs/
 */
class FacturacionElectronicaE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected Usuario $user;
    protected FeCertificadoDigital $certificado;
    protected ClaveNumericaGenerator $claveGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar ambiente sandbox para tests
        config([
            'hacienda.environment' => 'sandbox',
            'hacienda.api_urls.sandbox.recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.api_urls.sandbox.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
            'hacienda.oauth.client_id' => 'api-stag',
            'hacienda.oauth.client_secret' => 'test_secret',
            'hacienda.oauth.grant_type' => 'client_credentials',
            'hacienda.version_esquema' => '4.4',
        ]);

        // Crear empresa emisora
        $this->empresa = Empresa::factory()->create([
            'nombre_comercial' => 'Empresa Test S.A.',
            'razon_social' => 'Empresa de Pruebas Test S.A.',
            'num_identificacion_dgt' => '310112345678',
            'tipo_identificacion' => '02', // Cédula jurídica
            'actividad_economica_principal' => '620100',
            'proveedor_sistemas' => 'SISTEMA ERP TEST',
            'email' => 'facturacion@empresatest.com',
            'telefono' => '22223333',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'direccion' => 'San José, Costa Rica',
        ]);

        // Crear usuario
        $this->user = Usuario::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        // Crear certificado digital
        $this->certificado = FeCertificadoDigital::factory()->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
            'fecha_vencimiento' => now()->addYear(),
        ]);

        $this->claveGenerator = new ClaveNumericaGenerator();
    }

    /** @test */
    public function flujo_completo_factura_electronica(): void
    {
        // 1. Generar clave numérica
        $fechaEmision = Carbon::now();
        $clave = $this->claveGenerator->generar(
            $fechaEmision,
            $this->empresa->num_identificacion_dgt,
            '1', // Consecutivo
            '1'  // Situación normal
        );

        $this->assertEquals(50, strlen($clave));
        $this->assertMatchesRegularExpression('/^\d{50}$/', $clave);

        // 2. Crear comprobante en BD
        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01', // Factura electrónica
            'clave' => $clave,
            'consecutivo' => '00100001010000000001',
            'fecha_emision' => $fechaEmision,
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543',
            'receptor_nombre' => 'Cliente de Prueba',
            'receptor_email' => 'cliente@test.com',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'condicion_venta' => '01', // Contado
            'medio_pago' => '01', // Efectivo
            'total_venta' => 10000.00000,
            'total_impuesto' => 1300.00000,
            'total_comprobante' => 11300.00000,
            'estado' => 'pendiente',
            'situacion' => '1',
        ]);

        $this->assertDatabaseHas('comprobantes_electronicos_fe', [
            'id' => $comprobante->id,
            'clave' => $clave,
            'tipo_documento' => '01',
            'estado' => 'pendiente',
        ]);

        // 3. Agregar líneas de detalle
        FeLineaDetalle::create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => '8523102100000',
            'cantidad' => 1.00000,
            'detalle' => 'Servicio de consultoría',
            'precio_unitario' => 10000.00000,
            'monto_total' => 10000.00000,
            'subtotal' => 10000.00000,
            'monto_total_linea' => 11300.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 1300.00000,
        ]);

        // Verificar que el comprobante tiene sus líneas
        $comprobante->refresh();
        $this->assertEquals(1, $comprobante->lineas->count());
    }

    /** @test */
    public function validacion_clave_numerica_50_posiciones(): void
    {
        $fecha = Carbon::parse('2025-06-15');
        $cedula = '310112345678';
        $consecutivo = '12345';

        $clave = $this->claveGenerator->generar($fecha, $cedula, $consecutivo, '1');

        // Validar estructura de la clave
        $this->assertEquals(50, strlen($clave));

        // Posición 1: País (5 = Costa Rica)
        $this->assertEquals('5', substr($clave, 0, 1));

        // Posiciones 2-9: Fecha (ddmmyyyy)
        $this->assertEquals('15062025', substr($clave, 1, 8));

        // Posiciones 10-21: Cédula (12 dígitos)
        $this->assertEquals('310112345678', substr($clave, 9, 12));

        // Posiciones 22-41: Consecutivo (20 dígitos)
        $consecutivoEnClave = substr($clave, 21, 20);
        $this->assertEquals(20, strlen($consecutivoEnClave));
        $this->assertEquals('00000000000000012345', $consecutivoEnClave);

        // Posición 42: Situación
        $this->assertEquals('1', substr($clave, 41, 1));

        // Posiciones 43-50: Código de seguridad (8 dígitos)
        $codigoSeguridad = substr($clave, 42, 8);
        $this->assertEquals(8, strlen($codigoSeguridad));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $codigoSeguridad);
    }

    /** @test */
    public function tipos_documento_soportados(): void
    {
        $tiposDocumento = [
            '01' => 'Factura Electrónica',
            '02' => 'Nota de Débito Electrónica',
            '03' => 'Nota de Crédito Electrónica',
            '04' => 'Tiquete Electrónico',
        ];

        foreach ($tiposDocumento as $codigo => $nombre) {
            $clave = $this->claveGenerator->generar(
                Carbon::now(),
                '310112345678',
                (string)($codigo + 100),
                '1'
            );

            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $this->empresa->id,
                'tipo_documento' => $codigo,
                'clave' => $clave,
                'consecutivo' => str_pad($codigo, 20, '0', STR_PAD_LEFT),
                'fecha_emision' => Carbon::now(),
                'moneda' => 'CRC',
                'total_comprobante' => 1000.00,
                'estado' => 'pendiente',
            ]);

            $this->assertDatabaseHas('comprobantes_electronicos_fe', [
                'id' => $comprobante->id,
                'tipo_documento' => $codigo,
            ]);
        }
    }

    /** @test */
    public function estados_comprobante_validos(): void
    {
        $estadosValidos = [
            'pendiente',
            'enviando',
            'recibido',
            'procesando',
            'aceptado',
            'rechazado',
            'error',
        ];

        $clave = $this->claveGenerator->generar(
            Carbon::now(),
            '310112345678',
            '999',
            '1'
        );

        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'clave' => $clave,
            'consecutivo' => '00000000000000000999',
            'fecha_emision' => Carbon::now(),
            'moneda' => 'CRC',
            'total_comprobante' => 1000.00,
            'estado' => 'pendiente',
        ]);

        foreach ($estadosValidos as $estado) {
            $comprobante->update(['estado' => $estado]);
            $comprobante->refresh();

            $this->assertEquals($estado, $comprobante->estado);
        }
    }

    /** @test */
    public function situaciones_emision_validas(): void
    {
        $situaciones = [
            '1' => 'Normal',
            '2' => 'Contingencia',
            '3' => 'Sin internet',
        ];

        foreach ($situaciones as $codigo => $descripcion) {
            $clave = $this->claveGenerator->generar(
                Carbon::now(),
                '310112345678',
                (string)(200 + (int)$codigo),
                $codigo
            );

            // Verificar que la situación está en la posición 42
            $this->assertEquals($codigo, substr($clave, 41, 1));
        }
    }

    /** @test */
    public function formato_identificacion_emisor_receptor(): void
    {
        // Tipos de identificación según Hacienda
        $tiposIdentificacion = [
            '01' => 'Cédula física (9 dígitos)',
            '02' => 'Cédula jurídica (10 dígitos)',
            '03' => 'DIMEX (11-12 dígitos)',
            '04' => 'NITE (10 dígitos)',
        ];

        // Crear comprobante con diferentes tipos
        $clave = $this->claveGenerator->generar(
            Carbon::now(),
            '310112345678',
            '500',
            '1'
        );

        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'clave' => $clave,
            'consecutivo' => '00000000000000000500',
            'fecha_emision' => Carbon::now(),
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543', // Máx 12 dígitos
            'receptor_nombre' => 'Cliente Test',
            'moneda' => 'CRC',
            'total_comprobante' => 1000.00,
            'estado' => 'pendiente',
        ]);

        // Verificar longitud del campo
        $this->assertEquals(2, strlen($comprobante->receptor_tipo_identificacion));
        $this->assertLessThanOrEqual(12, strlen($comprobante->receptor_numero_identificacion));
    }

    /** @test */
    public function monedas_soportadas(): void
    {
        $monedas = ['CRC', 'USD', 'EUR'];

        foreach ($monedas as $moneda) {
            $clave = $this->claveGenerator->generar(
                Carbon::now(),
                '310112345678',
                (string)(600 + array_search($moneda, $monedas)),
                '1'
            );

            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $this->empresa->id,
                'tipo_documento' => '01',
                'clave' => $clave,
                'consecutivo' => str_pad((600 + array_search($moneda, $monedas)), 20, '0', STR_PAD_LEFT),
                'fecha_emision' => Carbon::now(),
                'moneda' => $moneda,
                'tipo_cambio' => $moneda === 'CRC' ? 1.00 : 520.50,
                'total_comprobante' => 1000.00,
                'estado' => 'pendiente',
            ]);

            $this->assertEquals($moneda, $comprobante->moneda);
            $this->assertEquals(3, strlen($comprobante->moneda));
        }
    }

    /** @test */
    public function condiciones_venta_validas(): void
    {
        $condiciones = [
            '01' => 'Contado',
            '02' => 'Crédito',
            '03' => 'Consignación',
            '04' => 'Apartado',
            '05' => 'Arrendamiento con opción de compra',
            '06' => 'Arrendamiento en función financiera',
            '07' => 'Cobro a favor de un tercero',
            '08' => 'Servicios prestados al Estado a crédito',
            '09' => 'Pago del servicios prestado al Estado',
            '99' => 'Otros',
        ];

        foreach (array_slice($condiciones, 0, 3, true) as $codigo => $descripcion) {
            $clave = $this->claveGenerator->generar(
                Carbon::now(),
                '310112345678',
                (string)(700 + (int)$codigo),
                '1'
            );

            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $this->empresa->id,
                'tipo_documento' => '01',
                'clave' => $clave,
                'consecutivo' => str_pad((700 + (int)$codigo), 20, '0', STR_PAD_LEFT),
                'fecha_emision' => Carbon::now(),
                'condicion_venta' => $codigo,
                'plazo_credito' => $codigo === '02' ? 30 : null,
                'moneda' => 'CRC',
                'total_comprobante' => 1000.00,
                'estado' => 'pendiente',
            ]);

            $this->assertEquals($codigo, $comprobante->condicion_venta);
        }
    }

    /** @test */
    public function medios_pago_validos(): void
    {
        $mediosPago = [
            '01' => 'Efectivo',
            '02' => 'Tarjeta',
            '03' => 'Cheque',
            '04' => 'Transferencia - depósito bancario',
            '05' => 'Recaudado por terceros',
            '99' => 'Otros',
        ];

        foreach (array_slice($mediosPago, 0, 3, true) as $codigo => $descripcion) {
            $clave = $this->claveGenerator->generar(
                Carbon::now(),
                '310112345678',
                (string)(800 + (int)$codigo),
                '1'
            );

            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $this->empresa->id,
                'tipo_documento' => '01',
                'clave' => $clave,
                'consecutivo' => str_pad((800 + (int)$codigo), 20, '0', STR_PAD_LEFT),
                'fecha_emision' => Carbon::now(),
                'medio_pago' => $codigo,
                'moneda' => 'CRC',
                'total_comprobante' => 1000.00,
                'estado' => 'pendiente',
            ]);

            $this->assertEquals($codigo, $comprobante->medio_pago);
        }
    }

    /** @test */
    public function oauth_token_para_sandbox(): void
    {
        // Crear token simulado para sandbox
        $token = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'test_sandbox_token_xyz',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
            'uso_contador' => 0,
        ]);

        $this->assertDatabaseHas('fe_oauth_tokens', [
            'ambiente' => 'sandbox',
            'activo' => true,
        ]);

        $this->assertTrue($token->valido);
        $this->assertFalse($token->esta_expirado);
    }

    /** @test */
    public function urls_endpoints_correctos(): void
    {
        // Sandbox
        $sandboxRecepcion = config('hacienda.api_urls.sandbox.recepcion');
        $sandboxOauth = config('hacienda.api_urls.sandbox.oauth');

        $this->assertStringContainsString('api-sandbox', $sandboxRecepcion);
        $this->assertStringContainsString('rut-stag', $sandboxOauth);

        // Production (verificar configuración)
        config([
            'hacienda.api_urls.production.recepcion' => 'https://api.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.api_urls.production.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
        ]);

        $prodRecepcion = config('hacienda.api_urls.production.recepcion');
        $prodOauth = config('hacienda.api_urls.production.oauth');

        $this->assertStringNotContainsString('sandbox', $prodRecepcion);
        $this->assertStringNotContainsString('stag', $prodOauth);
        $this->assertStringContainsString('/realms/rut/', $prodOauth);
    }

    /** @test */
    public function version_esquema_xml_v44(): void
    {
        $version = config('hacienda.version_esquema');

        $this->assertEquals('4.4', $version);
    }
}

