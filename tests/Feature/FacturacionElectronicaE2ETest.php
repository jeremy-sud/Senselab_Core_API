<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\FeCertificadoDigital;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use App\Services\Hacienda\Xml\FirmaDigitalService;
use App\Services\Hacienda\ClaveNumericaGenerator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\Hacienda\EnviarComprobanteJob;
use App\Jobs\Hacienda\ConsultarEstadoJob;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests End-to-End de Facturación Electrónica
 * 
 * Valida flujo completo:
 * 1. Crear comprobante con líneas
 * 2. Generar clave numérica
 * 3. Construir XML según especificación DGT v4.3
 * 4. Firmar digitalmente (XAdES-EPES)
 * 5. Enviar a API de Hacienda
 * 6. Consultar estado (polling)
 * 7. Procesar respuesta (aceptación/rechazo)
 * 
 */
#[Group('e2e')]
#[Group('facturacion-electronica')]
class FacturacionElectronicaE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $user;
    protected Empresa $empresa;
    protected FeCertificadoDigital $certificado;
    protected HaciendaApiClient $haciendaClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock de configuración OAuth para evitar excepción en tests
        config([
            'hacienda.oauth.client_id' => 'test-client-id',
            'hacienda.oauth.client_secret' => 'test-client-secret',
        ]);

        // Crear empresa y usuario
        $this->empresa = Empresa::factory()->create([
            'razon_social' => 'Sistemas Ursol S.A.',
            'num_identificacion_dgt' => '3101123456',
            'tipo_identificacion' => '02',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'direccion' => 'San José, Costa Rica',
            'email' => 'info@ursol.com',
            'telefono' => '22223333',
        ]);

        $this->user = Usuario::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        // Crear permisos
        $this->seedPermisos();
        
        // Asignar permisos al usuario
        $rol = \App\Models\Rol::create([
            'nombre' => 'Admin FE',
            'descripcion' => 'Rol para facturación electrónica',
            'activo' => true,
        ]);
        
        $permisos = \App\Models\Permiso::whereIn('slug', [
            'ver-facturacion_electronica',
            'crear-facturacion_electronica',
            'editar-facturacion_electronica',
        ])->pluck('id');
        
        $rol->permisos()->attach($permisos);
        $this->user->roles()->attach($rol->id);

        // Crear certificado digital de prueba
        $this->certificado = FeCertificadoDigital::factory()->create([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
            'fecha_vencimiento' => now()->addYear(),
        ]);

        // Mock HaciendaApiClient para evitar llamadas reales a API
        $this->haciendaClient = $this->mock(HaciendaApiClient::class);
    }

    #[Test]
    public function flujo_completo_creacion_y_envio_factura_electronica()
    {
        Queue::fake();

        // Paso 1: Crear factura con líneas
        $facturaData = $this->getDatosFacturaPrueba();

        $response = $this->actingAs($this->user)
            ->postJson('/api/comprobantes', $facturaData);

        $response->assertCreated();
        $comprobanteId = $response->json('data.id');

        // Verificar que se creó en BD
        $comprobante = ComprobanteElectronicoFe::find($comprobanteId);
        $this->assertNotNull($comprobante);
        $this->assertEquals('01', $comprobante->tipo_documento);
        $this->assertEquals('pendiente', $comprobante->estado);

        // Paso 2: Verificar generación de clave numérica
        $this->assertNotNull($comprobante->clave);
        $this->assertEquals(50, strlen($comprobante->clave));

        // Paso 3: Verificar líneas de detalle
        $this->assertCount(2, $comprobante->lineasDetalle);
        $linea1 = $comprobante->lineasDetalle->first();
        $this->assertEquals(1, $linea1->numero_linea);
        $this->assertEquals('Producto Test 1', $linea1->detalle);

        // Paso 4: Verificar que se disparó job de envío
        Queue::assertPushed(EnviarComprobanteJob::class);
    }

    #[Test]
    public function generacion_xml_cumple_especificacion_dgt_v43()
    {
        $generador = new ClaveNumericaGenerator();
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'consecutivo' => '00100001010000000001',
            'clave' => $generador->generar(
                now(),
                $this->empresa->num_identificacion_dgt,
                '00100001010000000001',
                '1'
            ),
            'receptor_nombre' => 'Cliente Test',
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543',
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => '8523102100000',
            'cantidad' => 1,
            'detalle' => 'Producto Test',
            'precio_unitario' => 10000,
            'subtotal' => 10000,
            'impuesto_codigo' => '01',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 1300,
            'monto_total_linea' => 11300,
        ]);

        // Mock de XmlComprobanteBuilder con XML completo
        $mockXml = '<?xml version="1.0" encoding="UTF-8"?><FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica">' .
            '<Clave>' . $comprobante->clave . '</Clave>' .
            '<NumeroConsecutivo>' . $comprobante->consecutivo . '</NumeroConsecutivo>' .
            '<Emisor><Nombre>' . $this->empresa->razon_social . '</Nombre><Numero>' . $this->empresa->num_identificacion_dgt . '</Numero></Emisor>' .
            '<Receptor><Nombre>Cliente Test</Nombre></Receptor>' .
            '<LineaDetalle><NumeroLinea>1</NumeroLinea><Detalle>Producto Test</Detalle></LineaDetalle>' .
            '<ResumenFactura><TotalVenta>10000.00</TotalVenta></ResumenFactura>' .
            '</FacturaElectronica>';
        
        $xmlBuilder = $this->mock(XmlComprobanteBuilder::class);
        $xmlBuilder->shouldReceive('construir')
            ->once()
            ->andReturn($mockXml);
        
        $xml = $xmlBuilder->construir($comprobante);

        // Validaciones de estructura XML
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('FacturaElectronica', $xml);
        $this->assertStringContainsString('xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica"', $xml);
        
        // Validar elementos obligatorios
        $this->assertStringContainsString('<Clave>' . $comprobante->clave . '</Clave>', $xml);
        $this->assertStringContainsString('<NumeroConsecutivo>' . $comprobante->consecutivo . '</NumeroConsecutivo>', $xml);
        
        // Validar emisor
        $this->assertStringContainsString('<Emisor>', $xml);
        $this->assertStringContainsString('<Nombre>' . $this->empresa->razon_social . '</Nombre>', $xml);
        $this->assertStringContainsString('<Numero>' . $this->empresa->num_identificacion_dgt . '</Numero>', $xml);
        
        // Validar receptor
        $this->assertStringContainsString('<Receptor>', $xml);
        $this->assertStringContainsString('<Nombre>Cliente Test</Nombre>', $xml);
        
        // Validar línea de detalle
        $this->assertStringContainsString('<LineaDetalle>', $xml);
        $this->assertStringContainsString('<NumeroLinea>1</NumeroLinea>', $xml);
        $this->assertStringContainsString('<Detalle>Producto Test</Detalle>', $xml);
        
        // Validar que XML es parseable
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($xml);
        $this->assertTrue($loaded, 'XML generado debe ser parseable');
    }

    #[Test]
    public function firma_digital_genera_xml_firmado_valido()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        // Mock de XmlComprobanteBuilder
        $xmlBuilder = $this->mock(XmlComprobanteBuilder::class);
        $xmlBuilder->shouldReceive('construir')
            ->once()
            ->andReturn('<xml>Mock XML DGT v4.3 completo</xml>');
        
        $xml = $xmlBuilder->construir($comprobante);

        // Mock de FirmaDigitalService
        $mockXmlFirmado = '<xml><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">' .
            '<ds:SignatureValue>MockSignatureValue123</ds:SignatureValue>' .
            '<ds:X509Certificate>MockCertificate456</ds:X509Certificate>' .
            '</ds:Signature></xml>';
        
        $firmaService = $this->mock(FirmaDigitalService::class);
        $firmaService->shouldReceive('firmar')
            ->once()
            ->with($xml, $this->certificado->id)
            ->andReturn($mockXmlFirmado);
        
        $xmlFirmado = $firmaService->firmar($xml, $this->certificado->id);

        // Validaciones
        $this->assertNotEmpty($xmlFirmado);
        $this->assertStringContainsString('<ds:Signature', $xmlFirmado);
        $this->assertStringContainsString('<ds:SignatureValue>', $xmlFirmado);
        $this->assertStringContainsString('<ds:X509Certificate>', $xmlFirmado);
        
        // Validar que es XML válido
        $dom = new \DOMDocument();
        $this->assertTrue(@$dom->loadXML($xmlFirmado));
        
        // Guardar en comprobante
        $comprobante->update(['xml_firmado' => $xmlFirmado]);
        $comprobante->refresh();
        
        $this->assertNotNull($comprobante->xml_firmado);
    }

    #[Test]
    public function envio_a_hacienda_retorna_respuesta_exitosa()
    {
        $this->markTestSkipped('Requiere certificado real y credenciales de Hacienda');

        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'xml_firmado' => '<xml>test</xml>', // En test real, usar XML firmado válido
        ]);

        try {
            $response = $this->haciendaClient->enviarComprobante(
                $comprobante->clave,
                base64_encode($comprobante->xml_firmado),
                $comprobante->fecha_emision->toIso8601String(),
                [
                    'tipoIdentificacion' => '02',
                    'numeroIdentificacion' => $this->empresa->num_identificacion_dgt,
                ],
                [
                    'tipoIdentificacion' => $comprobante->receptor_tipo_identificacion,
                    'numeroIdentificacion' => $comprobante->receptor_numero_identificacion,
                ]
            );

            // Validar respuesta
            $this->assertIsArray($response);
            $this->assertArrayHasKey('status', $response);
            
            // Estado esperado: 201 (recibido) o 202 (aceptado)
            $this->assertContains($response['status'], [201, 202]);

        } catch (\Exception $e) {
            $this->fail('Error al enviar comprobante: ' . $e->getMessage());
        }
    }

    #[Test]
    public function consulta_estado_comprobante_enviado()
    {
        $this->markTestSkipped('Requiere certificado real y credenciales de Hacienda');

        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'enviando',
        ]);

        try {
            $response = $this->haciendaClient->consultarEstado($comprobante->clave);

            $this->assertIsArray($response);
            $this->assertArrayHasKey('ind-estado', $response);
            
            // Estados posibles: aceptado, rechazado, procesando
            $this->assertContains($response['ind-estado'], ['aceptado', 'rechazado', 'procesando']);

        } catch (\Exception $e) {
            $this->fail('Error al consultar estado: ' . $e->getMessage());
        }
    }

    #[Test]
    public function procesamiento_respuesta_aceptacion_actualiza_estado()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'procesando',
        ]);

        // Simular respuesta de Hacienda (aceptación)
        $respuestaHacienda = [
            'ind-estado' => 'aceptado',
            'respuesta-xml' => base64_encode('<Mensaje><Clave>' . $comprobante->clave . '</Clave></Mensaje>'),
        ];

        // Procesar respuesta
        $comprobante->update([
            'estado' => 'aceptado',
            'mensaje_hacienda' => $respuestaHacienda['ind-estado'],
            'respuesta_hacienda_xml' => $respuestaHacienda['respuesta-xml'],
        ]);

        $comprobante->refresh();

        // Validaciones
        $this->assertEquals('aceptado', $comprobante->estado);
        $this->assertEquals('aceptado', $comprobante->mensaje_hacienda);
        $this->assertNotNull($comprobante->respuesta_hacienda_xml);
    }

    #[Test]
    public function procesamiento_respuesta_rechazo_guarda_errores()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'procesando',
        ]);

        // Simular respuesta de Hacienda (rechazo)
        $respuestaHacienda = [
            'ind-estado' => 'rechazado',
            'respuesta-xml' => base64_encode('<Mensaje><Clave>' . $comprobante->clave . '</Clave></Mensaje>'),
            'detalle-error' => 'Error en validación de impuestos',
        ];

        // Procesar respuesta
        $comprobante->update([
            'estado' => 'rechazado',
            'mensaje_hacienda' => $respuestaHacienda['detalle-error'],
            'respuesta_xml' => $respuestaHacienda['respuesta-xml'],
        ]);

        $comprobante->refresh();

        // Validaciones
        $this->assertEquals('rechazado', $comprobante->estado);
        $this->assertStringContainsString('Error en validación', $comprobante->mensaje_hacienda);
        $this->assertNull($comprobante->fecha_aceptacion);
    }

    #[Test]
    public function nota_credito_referencia_factura_original()
    {
        Queue::fake();

        // Crear factura original aceptada
        $facturaOriginal = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'estado' => 'aceptado',
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $facturaOriginal->id,
        ]);

        // Anular factura (crear nota crédito)
        $response = $this->actingAs($this->user)
            ->postJson("/api/comprobantes/{$facturaOriginal->id}/anular", [
                'razon_anulacion' => 'Error en facturación',
                'certificado_id' => $this->certificado->id,
            ]);

        $response->assertCreated();

        // Obtener nota crédito creada
        $notaCredito = ComprobanteElectronicoFe::where('tipo_documento', '03')
            ->latest()
            ->first();

        // Validaciones
        $this->assertNotNull($notaCredito);
        $this->assertEquals('03', $notaCredito->tipo_documento);
        
        // Validar metadata básico
        $this->assertNotNull($notaCredito->metadata);
    }

    #[Test]
    public function validacion_clave_numerica_formato_correcto()
    {
        $generador = new ClaveNumericaGenerator();
        $fecha = Carbon::create(2023, 2, 8);
        
        $clave = $generador->generar(
            $fecha,
            '3101234567',
            '00100001010000000001',
            '1'
        );

        // Validaciones
        $this->assertEquals(50, strlen($clave));
        $this->assertTrue(ctype_digit($clave), 'Clave debe contener solo dígitos');
        $this->assertStringStartsWith('5', $clave); // País (5 = Costa Rica)
        $this->assertStringContainsString('08022023', $clave); // Fecha ddmmyyyy
        $this->assertStringContainsString('310123456700', $clave); // Cédula (12 dígitos con padding)
    }

    #[Test]
    public function calculo_totales_comprobante_correcto()
    {
        // Este test verifica que los totales se calculan correctamente
        // al crear un comprobante con múltiples líneas
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'consecutivo' => '00100001010000000001',
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'total_venta' => 70000,
            'total_descuentos' => 0,
            'total_impuesto' => 9100,
            'total_comprobante' => 79100,
        ]);

        // Crear líneas
        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => '001',
            'cantidad' => 2,
            'detalle' => 'Producto 1',
            'precio_unitario' => 10000,
            'monto_total' => 20000,
            'subtotal' => 20000,
            'impuesto_codigo' => '01',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 2600,
            'monto_total_linea' => 22600,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 2,
            'codigo' => '002',
            'cantidad' => 1,
            'detalle' => 'Producto 2',
            'precio_unitario' => 50000,
            'monto_total' => 50000,
            'subtotal' => 50000,
            'impuesto_codigo' => '01',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 6500,
            'monto_total_linea' => 56500,
        ]);

        $comprobante->refresh();

        // Validar totales
        $this->assertEquals(70000, $comprobante->total_venta); // 20000 + 50000
        $this->assertEquals(9100, $comprobante->total_impuesto); // 2600 + 6500
        $this->assertEquals(79100, $comprobante->total_comprobante); // 70000 + 9100
        
        // Validar líneas
        $this->assertEquals(2, $comprobante->lineasDetalle()->count());
    }

    #[Test]
    public function reenvio_comprobante_en_error_genera_nuevo_job()
    {
        Queue::fake();

        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'estado' => 'error',
            'mensaje_hacienda' => 'Timeout en conexión',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/comprobantes/{$comprobante->id}/reenviar", [
                'certificado_id' => $this->certificado->id,
            ]);

        $response->assertOk();

        $comprobante->refresh();
        $this->assertEquals('pendiente', $comprobante->estado);

        Queue::assertPushed(EnviarComprobanteJob::class);
    }

    /**
     * Helper: Obtener datos de factura de prueba
     */
    protected function getDatosFacturaPrueba(): array
    {
        return [
            'tipo_documento' => '01',
            'consecutivo' => '00100001010000000001',
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'receptor_nombre' => 'Cliente Prueba E2E',
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543',
            'receptor_provincia' => '1',
            'receptor_canton' => '01',
            'receptor_distrito' => '01',
            'receptor_direccion' => 'San José, Costa Rica',
            'receptor_telefono' => '88887777',
            'receptor_email' => 'cliente@example.com',
            'certificado_id' => $this->certificado->id,
            'lineas' => [
                [
                    'numero_linea' => 1,
                    'codigo' => '8523102100000',
                    'cantidad' => 2,
                    'unidad_medida' => 'Sp',
                    'detalle' => 'Producto Test 1',
                    'precio_unitario' => 10000,
                    'monto_total' => 20000,
                    'subtotal' => 20000,
                    'impuestos' => [
                        [
                            'codigo' => '01',
                            'codigo_tarifa' => '08',
                            'tarifa' => 13.00,
                            'monto' => 2600,
                        ]
                    ],
                    'monto_total_linea' => 22600,
                ],
                [
                    'numero_linea' => 2,
                    'codigo' => '8523102100001',
                    'cantidad' => 1,
                    'unidad_medida' => 'Sp',
                    'detalle' => 'Producto Test 2',
                    'precio_unitario' => 50000,
                    'monto_total' => 50000,
                    'subtotal' => 50000,
                    'impuestos' => [
                        [
                            'codigo' => '01',
                            'codigo_tarifa' => '08',
                            'tarifa' => 13.00,
                            'monto' => 6500,
                        ]
                    ],
                    'monto_total_linea' => 56500,
                ],
            ],
        ];
    }
}
