<?php

namespace Tests\Feature\Hacienda;

use App\Models\ComprobanteElectronicoFe;
use App\Models\Empresa;
use App\Models\FeCertificadoDigital;
use App\Models\FeLineaDetalle;
use App\Models\FeOAuthToken;
use App\Services\Hacienda\ClaveNumericaGenerator;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\OAuthTokenManager;
use App\Services\Hacienda\Xml\XadesEpesSigner;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests E2E contra el Sandbox REAL de Hacienda Costa Rica
 *
 * Ejecuta el flujo completo de facturación electrónica contra los endpoints
 * reales del ambiente de pruebas (ATV) de Hacienda.
 *
 * Flujo: OAuth → Generar XML v4.4 → Firmar XAdES-EPES → Enviar → Consultar → Logout
 *
 * REQUISITOS:
 * - Variables de entorno HACIENDA_SANDBOX_* configuradas
 * - Archivo .p12 de pruebas en storage/app/certificates/sandbox/
 * - Acceso a internet (endpoints sandbox de Hacienda)
 *
 * EJECUCIÓN:
 *   php artisan test --group=e2e-sandbox
 *   make e2e-hacienda
 *
 * @see docs/hacienda/FACTURACION_ELECTRONICA_SETUP.md
 */
#[Group('e2e-sandbox')]
class HaciendaSandboxE2ETest extends TestCase
{
    use RefreshDatabase;

    private const CEDULA_EMISOR = '010647095827';
    private const TIPO_IDENTIFICACION_EMISOR = '01';
    private const RECEPTOR_ID = '109876543';
    private const CODIGO_CABYS = '4323000000000';

    protected Empresa $empresa;
    protected ClaveNumericaGenerator $claveGenerator;
    protected string $certPath;
    protected string $certPin;

    /**
     * Verificar que las credenciales de sandbox están disponibles.
     * Si no lo están, se salta el test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $username = env('HACIENDA_SANDBOX_USERNAME');
        $password = env('HACIENDA_SANDBOX_PASSWORD');
        $this->certPin = env('HACIENDA_SANDBOX_CERT_PIN', '');
        $this->certPath = env(
            'HACIENDA_SANDBOX_CERT_PATH',
            storage_path('app/certificates/sandbox/010647095827.p12')
        );

        if (empty($username) || empty($password) || empty($this->certPin)) {
            $this->markTestSkipped(
                'Credenciales sandbox no configuradas. ' .
                'Configura HACIENDA_SANDBOX_USERNAME, HACIENDA_SANDBOX_PASSWORD y HACIENDA_SANDBOX_CERT_PIN.'
            );
        }

        if (!file_exists($this->certPath)) {
            $this->markTestSkipped(
                "Certificado .p12 no encontrado en: {$this->certPath}. " .
                'Coloca el archivo .p12 de pruebas o configura HACIENDA_SANDBOX_CERT_PATH.'
            );
        }

        // Configurar hacienda para sandbox con credenciales reales
        config([
            'hacienda.environment' => 'sandbox',
            'hacienda.oauth.client_id' => 'api-stag',
            'hacienda.oauth.grant_type' => 'password',
            'hacienda.oauth.username' => $username,
            'hacienda.oauth.password' => $password,
            'hacienda.api_urls.sandbox.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
            'hacienda.api_urls.sandbox.logout' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/logout',
            'hacienda.api_urls.sandbox.recepcion' => 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
            'hacienda.certificate.path' => $this->certPath,
            'hacienda.certificate.password' => $this->certPin,
            'hacienda.version_esquema' => '4.4',
            'hacienda.proveedor_sistemas' => 'URSOL CAST ERP',
        ]);

        $this->empresa = Empresa::factory()->create([
            'nombre_comercial' => 'Empresa Sandbox Test',
            'razon_social' => 'PERSONA FISICA SANDBOX',
            'num_identificacion_dgt' => self::CEDULA_EMISOR,
            'tipo_identificacion' => self::TIPO_IDENTIFICACION_EMISOR,
            'actividad_economica_principal' => '620100',
            'proveedor_sistemas' => 'URSOL CAST ERP',
            'email' => 'sandbox@test.cr',
            'telefono' => '22220000',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'direccion' => 'San José, Costa Rica - Pruebas Sandbox',
        ]);

        $this->claveGenerator = new ClaveNumericaGenerator();
    }

    // =========================================================================
    // 1. AUTENTICACIÓN OAUTH
    // =========================================================================

    #[Test]
    public function puede_autenticarse_con_sandbox_oauth(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');

        $accessToken = $tokenManager->obtenerNuevoToken();

        $this->assertNotEmpty($accessToken);
        $this->assertIsString($accessToken);

        // Verificar que el token se guardó en BD
        $tokenRecord = FeOAuthToken::where('ambiente', 'sandbox')
            ->where('activo', true)
            ->first();

        $this->assertNotNull($tokenRecord);
        $this->assertEquals('sandbox', $tokenRecord->ambiente);
        $this->assertNotNull($tokenRecord->expires_at);
        $this->assertNotNull($tokenRecord->refresh_token);
        $this->assertFalse($tokenRecord->expirado);

        Log::info('[E2E Sandbox] OAuth autenticación exitosa', [
            'expires_in' => $tokenRecord->expires_in,
            'token_type' => $tokenRecord->token_type,
        ]);
    }

    #[Test]
    public function puede_refrescar_token_con_refresh_token(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');

        // Obtener token inicial
        $accessToken1 = $tokenManager->obtenerNuevoToken();
        $this->assertNotEmpty($accessToken1);

        // Refrescar token
        $accessToken2 = $tokenManager->refreshToken();
        $this->assertNotEmpty($accessToken2);

        // Los tokens deben ser diferentes
        $this->assertNotEquals($accessToken1, $accessToken2);

        // El token anterior debe estar inactivo
        $tokensActivos = FeOAuthToken::where('ambiente', 'sandbox')
            ->where('activo', true)
            ->count();

        $this->assertEquals(1, $tokensActivos);

        Log::info('[E2E Sandbox] Token refresh exitoso');
    }

    #[Test]
    public function puede_hacer_logout_oauth(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');

        // Obtener token
        $tokenManager->obtenerNuevoToken();

        // Hacer logout
        $resultado = $tokenManager->logout();
        $this->assertTrue($resultado);

        // Verificar que no quedan tokens activos
        $tokensActivos = FeOAuthToken::where('ambiente', 'sandbox')
            ->where('activo', true)
            ->count();

        $this->assertEquals(0, $tokensActivos);

        Log::info('[E2E Sandbox] Logout exitoso');
    }

    // =========================================================================
    // 2. GENERACIÓN XML v4.4
    // =========================================================================

    #[Test]
    public function genera_xml_v44_factura_electronica_valido(): void
    {
        $comprobante = $this->crearComprobanteFactura();

        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);

        $this->assertNotEmpty($xml);

        // Validar estructura XML
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml));

        // Verificar namespace v4.4
        $root = $doc->documentElement;
        $this->assertStringContainsString('v4.4', $root->namespaceURI);
        $this->assertEquals('FacturaElectronica', $root->localName);

        // Campos obligatorios v4.4
        $this->assertStringContainsString('<Clave>', $xml);
        $this->assertStringContainsString('<CodigoActividadEmisor>', $xml);
        $this->assertStringContainsString('<NumeroConsecutivo>', $xml);
        $this->assertStringContainsString('<FechaEmision>', $xml);
        $this->assertStringContainsString('<ProveedorSistemas>', $xml);
        $this->assertStringContainsString('<Emisor>', $xml);
        $this->assertStringContainsString('<DetalleServicio>', $xml);
        $this->assertStringContainsString('<ResumenFactura>', $xml);

        Log::info('[E2E Sandbox] XML v4.4 generado correctamente', [
            'xml_length' => strlen($xml),
        ]);
    }

    // =========================================================================
    // 3. FIRMA DIGITAL XAdES-EPES
    // =========================================================================

    #[Test]
    public function firma_xml_con_certificado_sandbox_p12(): void
    {
        $comprobante = $this->crearComprobanteFactura();

        // Generar XML
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);

        // Extraer clave privada y certificado del .p12
        [$privateKey, $certificate] = $this->extraerCertificado();

        // Firmar con XAdES-EPES
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);

        $this->assertNotEmpty($xmlFirmado);
        $this->assertStringContainsString('ds:Signature', $xmlFirmado);
        $this->assertStringContainsString('xades:QualifyingProperties', $xmlFirmado);
        $this->assertStringContainsString('xades:SignaturePolicyIdentifier', $xmlFirmado);
        $this->assertStringContainsString('xades:SignedProperties', $xmlFirmado);

        // Verificar que el XML firmado sigue siendo XML válido
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xmlFirmado));

        Log::info('[E2E Sandbox] XML firmado con XAdES-EPES exitosamente', [
            'xml_length' => strlen($xmlFirmado),
            'has_signature' => str_contains($xmlFirmado, 'ds:Signature'),
        ]);
    }

    // =========================================================================
    // 4. ENVÍO A SANDBOX
    // =========================================================================

    #[Test]
    public function envia_factura_electronica_a_sandbox(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');
        $comprobante = $this->crearComprobanteFactura();

        // Generar XML v4.4
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);

        // Firmar XAdES-EPES
        [$privateKey, $certificate] = $this->extraerCertificado();
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);

        // Codificar en base64
        $xmlBase64 = base64_encode($xmlFirmado);

        // Obtener token
        $tokenManager->obtenerNuevoToken();

        // Enviar al sandbox
        $apiClient = new HaciendaApiClient('sandbox');
        $respuesta = $apiClient->enviarComprobante(
            $comprobante->clave,
            $xmlBase64,
            $comprobante->fecha_emision->toIso8601String(),
            $this->datosEmisor(),
            $this->datosReceptor()
        );

        // Sandbox debe aceptar el envío (202 Accepted o 200 OK)
        $this->assertEnvioAceptado($respuesta, 'comprobante');

        // Actualizar comprobante en BD
        $comprobante->update([
            'estado' => 'enviando',
            'xml_firmado' => $xmlFirmado,
            'fecha_envio' => now(),
        ]);

        Log::info('[E2E Sandbox] Factura enviada al sandbox', [
            'clave' => $comprobante->clave,
            'status_code' => $respuesta['status_code'],
        ]);

        // Cleanup: logout
        $tokenManager->logout();
    }

    #[Test]
    public function envia_tiquete_electronico_sin_receptor(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');
        $comprobante = $this->crearComprobanteTiquete();

        // Generar XML v4.4
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);

        // Firmar
        [$privateKey, $certificate] = $this->extraerCertificado();
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);

        // Enviar
        $tokenManager->obtenerNuevoToken();
        $apiClient = new HaciendaApiClient('sandbox');

        $respuesta = $apiClient->enviarComprobante(
            $comprobante->clave,
            base64_encode($xmlFirmado),
            $comprobante->fecha_emision->toIso8601String(),
            $this->datosEmisor()
        );

        $this->assertEnvioAceptado($respuesta, 'tiquete');

        Log::info('[E2E Sandbox] Tiquete electrónico enviado al sandbox', [
            'clave' => $comprobante->clave,
            'status_code' => $respuesta['status_code'],
        ]);

        $tokenManager->logout();
    }

    // =========================================================================
    // 5. CONSULTA DE ESTADO
    // =========================================================================

    #[Test]
    public function consulta_estado_comprobante_enviado(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');
        $comprobante = $this->crearComprobanteFactura();

        // Generar, firmar y enviar
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);

        [$privateKey, $certificate] = $this->extraerCertificado();
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);

        $tokenManager->obtenerNuevoToken();
        $apiClient = new HaciendaApiClient('sandbox');

        $envio = $apiClient->enviarComprobante(
            $comprobante->clave,
            base64_encode($xmlFirmado),
            $comprobante->fecha_emision->toIso8601String(),
            $this->datosEmisor(),
            $this->datosReceptor()
        );

        // Solo consultar si el envío fue aceptado
        if (!in_array($envio['status_code'], [200, 201, 202])) {
            $this->markTestSkipped(
                "Envío no fue aceptado (status: {$envio['status_code']}), no se puede consultar estado."
            );
        }

        // Esperar brevemente para que el sandbox procese
        sleep(3);

        // Consultar estado
        $estado = $apiClient->consultarEstado($comprobante->clave);

        $this->assertIsArray($estado);
        $this->assertArrayHasKey('status_code', $estado);

        // Status code 200 = comprobante encontrado, 404 = aún procesando
        $this->assertContains(
            $estado['status_code'],
            [200, 404],
            "Estado inesperado al consultar comprobante. Status: {$estado['status_code']}"
        );

        if ($estado['status_code'] === 200 && isset($estado['data'])) {
            Log::info('[E2E Sandbox] Estado del comprobante consultado', [
                'clave' => $comprobante->clave,
                'ind-estado' => $estado['data']['ind-estado'] ?? 'N/A',
                'respuesta-xml' => isset($estado['data']['respuesta-xml']) ? 'presente' : 'ausente',
            ]);

            // Si hay respuesta, verificar campos básicos
            if (isset($estado['data']['ind-estado'])) {
                $this->assertContains(
                    $estado['data']['ind-estado'],
                    ['aceptado', 'rechazado', 'procesando'],
                    'Estado de Hacienda no reconocido'
                );
            }
        }

        $tokenManager->logout();
    }

    // =========================================================================
    // 6. FLUJO COMPLETO E2E
    // =========================================================================

    #[Test]
    public function flujo_completo_factura_electronica_sandbox(): void
    {
        Log::info('[E2E Sandbox] === INICIO FLUJO COMPLETO ===');

        // PASO 1: Autenticación OAuth
        $tokenManager = new OAuthTokenManager('sandbox');
        $accessToken = $tokenManager->obtenerNuevoToken();
        $this->assertNotEmpty($accessToken);
        Log::info('[E2E Sandbox] Paso 1/6: OAuth OK');

        // PASO 2: Generar clave numérica + XML v4.4
        $comprobante = $this->crearComprobanteFactura();
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($comprobante);
        $this->assertStringContainsString('FacturaElectronica', $xml);
        $this->assertStringContainsString('ProveedorSistemas', $xml);
        Log::info('[E2E Sandbox] Paso 2/6: XML v4.4 generado');

        // PASO 3: Firmar con XAdES-EPES usando certificado .p12 de sandbox
        [$privateKey, $certificate] = $this->extraerCertificado();
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);
        $this->assertStringContainsString('ds:Signature', $xmlFirmado);
        Log::info('[E2E Sandbox] Paso 3/6: XAdES-EPES firmado');

        // PASO 4: Enviar a sandbox de Hacienda
        $apiClient = new HaciendaApiClient('sandbox');
        $envio = $apiClient->enviarComprobante(
            $comprobante->clave,
            base64_encode($xmlFirmado),
            $comprobante->fecha_emision->toIso8601String(),
            $this->datosEmisor(),
            $this->datosReceptor()
        );

        $this->assertEnvioAceptado($envio, 'comprobante');
        Log::info('[E2E Sandbox] Paso 4/6: Comprobante enviado', [
            'status' => $envio['status_code'],
        ]);

        // Guardar XML firmado en BD
        $comprobante->update([
            'xml_original' => $xml,
            'xml_firmado' => $xmlFirmado,
            'estado' => 'enviando',
            'fecha_envio' => now(),
        ]);

        // PASO 5: Consultar estado (con retry)
        sleep(3);
        $estado = $apiClient->consultarEstado($comprobante->clave);
        $this->assertIsArray($estado);
        $this->assertArrayHasKey('status_code', $estado);

        if ($estado['status_code'] === 200 && isset($estado['data']['ind-estado'])) {
            $estadoHacienda = $estado['data']['ind-estado'];
            $comprobante->update([
                'estado' => $estadoHacienda,
                'fecha_respuesta' => now(),
                'respuesta_hacienda_xml' => $estado['data']['respuesta-xml'] ?? null,
            ]);
            Log::info('[E2E Sandbox] Paso 5/6: Estado consultado', [
                'estado' => $estadoHacienda,
            ]);
        } else {
            Log::info('[E2E Sandbox] Paso 5/6: Comprobante aún en procesamiento', [
                'status_code' => $estado['status_code'],
            ]);
        }

        // PASO 6: Logout OAuth
        $resultado = $tokenManager->logout();
        $this->assertTrue($resultado);
        Log::info('[E2E Sandbox] Paso 6/6: Logout OK');

        Log::info('[E2E Sandbox] === FLUJO COMPLETO EXITOSO ===', [
            'clave' => $comprobante->clave,
        ]);
    }

    #[Test]
    public function flujo_completo_nota_credito_sandbox(): void
    {
        $tokenManager = new OAuthTokenManager('sandbox');
        $tokenManager->obtenerNuevoToken();

        // Primero enviar una factura (documento de referencia)
        $factura = $this->crearComprobanteFactura();
        $xmlBuilder = new XmlComprobanteBuilder();
        $xml = $xmlBuilder->build($factura);

        [$privateKey, $certificate] = $this->extraerCertificado();
        $signer = new XadesEpesSigner();
        $xmlFirmado = $signer->sign($xml, $privateKey, $certificate);

        $apiClient = new HaciendaApiClient('sandbox');
        $envioFactura = $apiClient->enviarComprobante(
            $factura->clave,
            base64_encode($xmlFirmado),
            $factura->fecha_emision->toIso8601String(),
            $this->datosEmisor(),
            $this->datosReceptor()
        );

        if (!in_array($envioFactura['status_code'], [200, 201, 202])) {
            $tokenManager->logout();
            $this->markTestSkipped(
                "No se pudo enviar la factura base. Status: {$envioFactura['status_code']}"
            );
        }

        // Ahora enviar la nota de crédito
        $notaCredito = $this->crearComprobanteNotaCredito($factura);
        $xmlNC = $xmlBuilder->build($notaCredito);
        $xmlNCFirmado = $signer->sign($xmlNC, $privateKey, $certificate);

        $envioNC = $apiClient->enviarComprobante(
            $notaCredito->clave,
            base64_encode($xmlNCFirmado),
            $notaCredito->fecha_emision->toIso8601String(),
            $this->datosEmisor(),
            $this->datosReceptor()
        );

        $this->assertEnvioAceptado($envioNC, 'nota de crédito');

        Log::info('[E2E Sandbox] Nota de crédito enviada', [
            'clave_factura' => $factura->clave,
            'clave_nc' => $notaCredito->clave,
            'status' => $envioNC['status_code'],
        ]);

        $tokenManager->logout();
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Crear comprobante tipo Factura Electrónica (01) con líneas de detalle.
     */
    private function crearComprobanteFactura(): ComprobanteElectronicoFe
    {
        $fechaEmision = Carbon::now();
        $consecutivo = '00100001010000020010';

        $clave = $this->claveGenerator->generar(
            $fechaEmision,
            self::CEDULA_EMISOR,
            $consecutivo,
            '1'
        );

        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'clave' => $clave,
            'consecutivo' => $consecutivo,
            'fecha_emision' => $fechaEmision,
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => self::RECEPTOR_ID,
            'receptor_nombre' => 'RECEPTOR PRUEBA SANDBOX',
            'receptor_email' => 'receptor@test.cr',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'total_servicios_gravados' => 10000.00000,
            'total_gravado' => 10000.00000,
            'total_venta' => 10000.00000,
            'total_venta_neta' => 10000.00000,
            'total_impuesto' => 1300.00000,
            'total_comprobante' => 11300.00000,
            'estado' => 'pendiente',
            'situacion' => '1',
        ]);

        FeLineaDetalle::create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => self::CODIGO_CABYS,
            'cantidad' => 1.00000,
            'detalle' => 'Servicio de consultoría - Prueba Sandbox',
            'precio_unitario' => 10000.00000,
            'monto_total' => 10000.00000,
            'subtotal' => 10000.00000,
            'monto_total_linea' => 11300.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 1300.00000,
        ]);

        $comprobante->load('lineas');
        return $comprobante;
    }

    /**
     * Crear comprobante tipo Tiquete Electrónico (04) sin receptor.
     */
    private function crearComprobanteTiquete(): ComprobanteElectronicoFe
    {
        $fechaEmision = Carbon::now();
        $consecutivo = '00400001010000020000';

        $clave = $this->claveGenerator->generar(
            $fechaEmision,
            self::CEDULA_EMISOR,
            $consecutivo,
            '1'
        );

        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '04',
            'clave' => $clave,
            'consecutivo' => $consecutivo,
            'fecha_emision' => $fechaEmision,
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'total_servicios_gravados' => 5000.00000,
            'total_gravado' => 5000.00000,
            'total_venta' => 5000.00000,
            'total_venta_neta' => 5000.00000,
            'total_impuesto' => 650.00000,
            'total_comprobante' => 5650.00000,
            'estado' => 'pendiente',
            'situacion' => '1',
        ]);

        FeLineaDetalle::create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => self::CODIGO_CABYS,
            'cantidad' => 1.00000,
            'detalle' => 'Producto tiquete - Prueba Sandbox',
            'precio_unitario' => 5000.00000,
            'monto_total' => 5000.00000,
            'subtotal' => 5000.00000,
            'monto_total_linea' => 5650.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 650.00000,
        ]);

        $comprobante->load('lineas');
        return $comprobante;
    }

    /**
     * Crear comprobante tipo Nota de Crédito (03) referenciando una factura.
     */
    private function crearComprobanteNotaCredito(ComprobanteElectronicoFe $facturaReferencia): ComprobanteElectronicoFe
    {
        $fechaEmision = Carbon::now();
        $consecutivo = '00300001010000020000';

        $clave = $this->claveGenerator->generar(
            $fechaEmision,
            self::CEDULA_EMISOR,
            $consecutivo,
            '1'
        );

        $comprobante = ComprobanteElectronicoFe::create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '03',
            'clave' => $clave,
            'consecutivo' => $consecutivo,
            'fecha_emision' => $fechaEmision,
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => self::RECEPTOR_ID,
            'receptor_nombre' => 'RECEPTOR PRUEBA SANDBOX',
            'receptor_email' => 'receptor@test.cr',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'total_servicios_gravados' => 10000.00000,
            'total_gravado' => 10000.00000,
            'total_venta' => 10000.00000,
            'total_venta_neta' => 10000.00000,
            'total_impuesto' => 1300.00000,
            'total_comprobante' => 11300.00000,
            'estado' => 'pendiente',
            'situacion' => '1',
            'metadata' => [
                'informacion_referencia' => [
                    [
                        'tipo_doc' => '01',
                        'numero' => $facturaReferencia->clave,
                        'fecha_emision' => $facturaReferencia->fecha_emision->toIso8601String(),
                        'codigo' => '01',
                        'razon' => 'Anulación de factura por prueba sandbox',
                    ],
                ],
            ],
        ]);

        FeLineaDetalle::create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => self::CODIGO_CABYS,
            'cantidad' => 1.00000,
            'detalle' => 'Anulación: Servicio de consultoría - Prueba Sandbox',
            'precio_unitario' => 10000.00000,
            'monto_total' => 10000.00000,
            'subtotal' => 10000.00000,
            'monto_total_linea' => 11300.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 1300.00000,
        ]);

        $comprobante->load('lineas');
        return $comprobante;
    }

    /**
     * Extraer clave privada y certificado del archivo .p12 de sandbox como strings PEM.
     *
     * @return array{0: string, 1: string} [privateKeyPem, certificatePem]
     */
    private function extraerCertificado(): array
    {
        $p12Content = file_get_contents($this->certPath);
        $this->assertNotFalse($p12Content, "No se pudo leer el archivo .p12: {$this->certPath}");

        $certs = [];
        $success = openssl_pkcs12_read($p12Content, $certs, $this->certPin);
        $this->assertTrue(
            $success,
            'No se pudo parsear el certificado .p12. PIN incorrecto o archivo corrupto. ' .
            'OpenSSL: ' . openssl_error_string()
        );

        $this->assertArrayHasKey('pkey', $certs, 'El .p12 no contiene clave privada');
        $this->assertArrayHasKey('cert', $certs, 'El .p12 no contiene certificado');

        return [$certs['pkey'], $certs['cert']];
    }

    /**
     * Datos del emisor para envío a Hacienda.
     *
     * @return array<string, string>
     */
    private function datosEmisor(): array
    {
        return [
            'tipoIdentificacion' => self::TIPO_IDENTIFICACION_EMISOR,
            'numeroIdentificacion' => self::CEDULA_EMISOR,
        ];
    }

    /**
     * Datos del receptor para envío a Hacienda.
     *
     * @return array<string, string>
     */
    private function datosReceptor(): array
    {
        return [
            'tipoIdentificacion' => '01',
            'numeroIdentificacion' => self::RECEPTOR_ID,
        ];
    }

    /**
     * Assert que el envío fue aceptado por el sandbox.
     *
     * @param array<string, mixed> $respuesta
     */
    private function assertEnvioAceptado(array $respuesta, string $tipoDocumento): void
    {
        $this->assertIsArray($respuesta);
        $this->assertArrayHasKey('status_code', $respuesta);
        $this->assertContains(
            $respuesta['status_code'],
            [200, 201, 202],
            "Sandbox rechazó el {$tipoDocumento}. Status: {$respuesta['status_code']}. " .
            'Respuesta: ' . json_encode($respuesta['data'] ?? $respuesta['error'] ?? 'sin datos')
        );
    }
}
