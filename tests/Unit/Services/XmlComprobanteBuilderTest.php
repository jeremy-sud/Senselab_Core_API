<?php

namespace Tests\Unit\Services;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\Empresa;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests para XmlComprobanteBuilder
 *
 * Valida:
 * - Generación de XML v4.4 correcto (actualizado desde v4.3)
 * - Estructura para cada tipo de documento
 * - Namespaces correctos v4.4
 * - Formato de datos (decimales, fechas)
 * - Elementos opcionales y obligatorios
 * - Nuevo campo ProveedorSistemas (obligatorio en v4.4)
 * - Campo CodigoActividadEmisor (renombrado en v4.4)
 */
class XmlComprobanteBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected XmlComprobanteBuilder $builder;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new XmlComprobanteBuilder();
        
        // Crear empresa de prueba con campos v4.4
        $this->empresa = Empresa::factory()->create([
            'nombre_comercial' => 'Empresa Test',
            'razon_social' => 'Empresa Test S.A.',
            'num_identificacion_dgt' => '310112345678',
            'tipo_identificacion' => '02',
            'actividad_economica_principal' => '620100', // Código actividad económica
            'proveedor_sistemas' => 'SISTEMA ERP TEST', // Nuevo en v4.4
            'email' => 'test@empresa.com',
            'telefono' => '88887777',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'direccion' => 'San José, Costa Rica',
        ]);
    }

    #[Test]
    public function genera_xml_valido_para_factura()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'clave' => '52611202531011234567800000000000000000001154489877',
            'consecutivo' => '00000000000000000001',
            'fecha_emision' => Carbon::parse('2025-11-26 10:30:00'),
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'receptor_nombre' => 'Cliente Test',
            'receptor_numero_identificacion' => '109876543',
            'receptor_tipo_identificacion' => '01',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'numero_linea' => 1,
            'codigo' => '8523102100000',
            'cantidad' => 2.00000,
            'detalle' => 'Producto de prueba',
            'precio_unitario' => 10000.00000,
            'monto_total' => 20000.00000,
            'subtotal' => 20000.00000,
            'monto_total_linea' => 22600.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 2600.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        // Verificar que es XML válido
        $this->assertNotEmpty($xml);
        
        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($xml);
        $this->assertTrue($loaded, 'El XML generado no es válido');

        // Verificar namespace correcto para factura v4.4
        $this->assertStringContainsString('https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica', $xml);
        
        // Verificar campos nuevos v4.4
        $this->assertStringContainsString('<CodigoActividadEmisor>620100</CodigoActividadEmisor>', $xml);
        $this->assertStringContainsString('<ProveedorSistemas>SISTEMA ERP TEST</ProveedorSistemas>', $xml);
        
        // Verificar clave
        $this->assertStringContainsString('<Clave>52611202531011234567800000000000000000001154489877</Clave>', $xml);
        
        // Verificar emisor
        $this->assertStringContainsString('<Nombre>Empresa Test S.A.</Nombre>', $xml);
        $this->assertStringContainsString('<Numero>310112345678</Numero>', $xml);
        
        // Verificar receptor
        $this->assertStringContainsString('<Nombre>Cliente Test</Nombre>', $xml);
        
        // Verificar fecha ISO 8601
        $this->assertStringContainsString('2025-11-26T10:30:00', $xml);
    }

    #[Test]
    public function genera_xml_valido_para_tiquete_sin_receptor()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '04', // Tiquete
            'clave' => '52611202531011234567800000000000000000002154489877',
            'receptor_nombre' => null, // Sin receptor
            'receptor_numero_identificacion' => null,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $xml = $this->builder->build($comprobante);

        // Verificar namespace para tiquete
        $this->assertStringContainsString('tiqueteElectronico', $xml);
        
        // No debería contener sección de receptor
        $this->assertStringNotContainsString('<Receptor>', $xml);
    }

    #[Test]
    public function genera_xml_valido_para_nota_credito_con_referencia()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '03', // Nota Crédito
            'metadata' => [
                'documento_referencia' => [
                    'tipo_documento' => '01',
                    'numero' => '00000000000000000001',
                    'fecha_emision' => '2025-11-20',
                    'codigo' => '01',
                    'razon' => 'Anulación de factura',
                ],
            ],
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $xml = $this->builder->build($comprobante);

        // Verificar namespace para nota crédito
        $this->assertStringContainsString('notaCreditoElectronica', $xml);
        
        // Verificar información de referencia
        $this->assertStringContainsString('<InformacionReferencia>', $xml);
        $this->assertStringContainsString('<TipoDoc>01</TipoDoc>', $xml);
        $this->assertStringContainsString('<Numero>00000000000000000001</Numero>', $xml);
        $this->assertStringContainsString('<Codigo>01</Codigo>', $xml);
        $this->assertStringContainsString('<Razon>Anulación de factura</Razon>', $xml);
    }

    #[Test]
    public function formatea_decimales_correctamente()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'cantidad' => 2.5,
            'precio_unitario' => 1234.56789,
            'monto_total' => 3086.42,
        ]);

        $xml = $this->builder->build($comprobante);

        // Verificar formato de 5 decimales
        $this->assertStringContainsString('<Cantidad>2.50000</Cantidad>', $xml);
        $this->assertStringContainsString('<PrecioUnitario>1234.56789</PrecioUnitario>', $xml);
        $this->assertStringContainsString('<MontoTotal>3086.42000</MontoTotal>', $xml);
    }

    #[Test]
    public function escapa_caracteres_xml_correctamente()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'receptor_nombre' => 'Cliente & Asociados <Test>',
            'metadata' => [
                'otros' => [
                    'Nota con "comillas" y \'apóstrofes\'',
                ],
            ],
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'detalle' => 'Producto con & símbolos < >',
        ]);

        $xml = $this->builder->build($comprobante);

        // Verificar que los caracteres especiales están escapados
        $this->assertStringContainsString('Cliente &amp; Asociados &lt;Test&gt;', $xml);
        $this->assertStringContainsString('Producto con &amp; símbolos &lt; &gt;', $xml);
        // Verificar que las comillas están presentes (no necesariamente como &quot; en contenido de texto)
        $this->assertStringContainsString('comillas', $xml);
    }

    #[Test]
    public function incluye_impuestos_en_lineas()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 2600.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Impuesto>', $xml);
        $this->assertStringContainsString('<Codigo>01</Codigo>', $xml);
        $this->assertStringContainsString('<CodigoTarifaIVA>08</CodigoTarifaIVA>', $xml);
        $this->assertStringContainsString('<Tarifa>13.00000</Tarifa>', $xml);
        $this->assertStringContainsString('<Monto>2600.00000</Monto>', $xml);
    }

    #[Test]
    public function incluye_descuentos_cuando_existen()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
            'monto_descuento' => 1000.00000,
            'naturaleza_descuento' => 'Descuento por volumen',
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Descuento>', $xml);
        $this->assertStringContainsString('<MontoDescuento>1000.00000</MontoDescuento>', $xml);
        $this->assertStringContainsString('<NaturalezaDescuento>Descuento por volumen</NaturalezaDescuento>', $xml);
    }

    #[Test]
    public function incluye_totales_correctamente()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
            'total_venta' => 20000.00000,
            'total_descuentos' => 1000.00000,
            'total_venta_neta' => 19000.00000,
            'total_impuesto' => 2470.00000,
            'total_comprobante' => 21470.00000,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<ResumenFactura>', $xml);
        $this->assertStringContainsString('<TotalVenta>20000.00000</TotalVenta>', $xml);
        $this->assertStringContainsString('<TotalDescuentos>1000.00000</TotalDescuentos>', $xml);
        $this->assertStringContainsString('<TotalVentaNeta>19000.00000</TotalVentaNeta>', $xml);
        $this->assertStringContainsString('<TotalImpuesto>2470.00000</TotalImpuesto>', $xml);
        $this->assertStringContainsString('<TotalComprobante>21470.00000</TotalComprobante>', $xml);
    }

    #[Test]
    public function xml_es_bien_formado()
    {
        $comprobante = ComprobanteElectronicoFe::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        FeLineaDetalle::factory()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $xml = $this->builder->build($comprobante);

        // Validar que el XML es bien formado
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        
        $loaded = @$dom->loadXML($xml);
        $this->assertTrue($loaded);
        
        // Verificar que tiene declaración XML
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
    }
}
