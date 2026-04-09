<?php

namespace Tests\Unit\Services;

use App\Models\ComprobanteElectronicoFe;
use App\Models\Empresa;
use App\Models\FeInformacionReferencia;
use App\Models\FeLineaDescuento;
use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use App\Models\FeMedioPago;
use App\Models\FeOtroCargo;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests para XmlComprobanteBuilder — Brechas Hacienda v4.4
 *
 * Valida las correcciones de cumplimiento con DGT-R-000-2024 v4.4:
 * - Brecha #2: CodigoDescuento obligatorio
 * - Brecha #3: TotalDesgloseImpuesto con CodigoTarifaIVA
 * - Brecha #4: Ubicación del receptor
 * - Brecha #5: Múltiples impuestos por línea
 * - Brecha #6: Múltiples medios de pago
 * - Brecha #7: Emisor Barrio desde DB
 * - Brecha #9: CodigoActividadReceptor
 * - Brecha #10: CondicionVentaOtros
 * - Brecha #11: NombreComercial emisor en XML
 * - Brecha #12: NombreComercial receptor
 * - Brecha #13: OtrosCargos estructura completa
 * - Brecha #14: InformacionReferencia como tabla
 * - Brecha #15: Múltiples descuentos por línea
 * - Brecha #19: Totales exonerados en XML
 * - Brecha #26: Totales No Sujeto
 * - Brecha #30: TotalIVADevuelto en XML
 * - Brecha #35: Teléfono del receptor
 */
class XmlComprobanteBuilderV44Test extends TestCase
{
    use RefreshDatabase;

    protected XmlComprobanteBuilder $builder;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new XmlComprobanteBuilder();

        $this->empresa = Empresa::factory()->create([
            'razon_social' => 'Test S.A.',
            'nombre_comercial' => 'Test Commerce',
            'num_identificacion_dgt' => '3101123456',
            'tipo_identificacion' => '02',
            'actividad_economica_principal' => '620100',
            'proveedor_sistemas' => 'URSOL CAST',
            'email' => 'test@test.com',
            'telefono' => '22223333',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'barrio' => '03',
            'direccion' => 'San José, Costa Rica',
        ]);
    }

    protected function crearComprobante(array $attrs = []): ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::factory()->create(array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_documento' => '01',
            'clave' => '52611202531011234567800000000000000000001154489877',
            'consecutivo' => '00000000000000000001',
            'fecha_emision' => Carbon::parse('2026-04-07 10:00:00'),
            'condicion_venta' => '01',
            'medio_pago' => '01',
            'receptor_nombre' => 'Cliente Test',
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => '109876543',
            'receptor_email' => 'cliente@test.com',
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'total_venta' => 20000.00000,
            'total_venta_neta' => 20000.00000,
            'total_impuesto' => 2600.00000,
            'total_comprobante' => 22600.00000,
            'total_gravado' => 20000.00000,
            'total_exento' => 0.00000,
        ], $attrs));
    }

    protected function crearLinea(int $comprobanteId, array $attrs = []): FeLineaDetalle
    {
        return FeLineaDetalle::factory()->create(array_merge([
            'comprobante_id' => $comprobanteId,
            'numero_linea' => 1,
            'codigo_cabys' => '8523102100000',
            'cantidad' => 1.00000,
            'unidad_medida' => 'Sp',
            'detalle' => 'Producto test',
            'precio_unitario' => 20000.00000,
            'monto_total' => 20000.00000,
            'subtotal' => 20000.00000,
            'base_imponible' => 20000.00000,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 2600.00000,
            'impuesto_neto' => 2600.00000,
            'monto_total_linea' => 22600.00000,
            'monto_descuento' => 0,
        ], $attrs));
    }

    // ==========================================
    // Brecha #2: CodigoDescuento obligatorio
    // ==========================================

    #[Test]
    public function brecha2_descuento_legacy_incluye_codigo_descuento()
    {
        $comprobante = $this->crearComprobante();
        $this->crearLinea($comprobante->id, [
            'monto_descuento' => 1000.00000,
            'codigo_descuento' => '07',
            'naturaleza_descuento' => 'Descuento comercial',
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CodigoDescuento>07</CodigoDescuento>', $xml);
        $this->assertStringContainsString('<MontoDescuento>1000.00000</MontoDescuento>', $xml);
    }

    #[Test]
    public function brecha2_descuento_sin_codigo_usa_default_07()
    {
        $comprobante = $this->crearComprobante();
        $this->crearLinea($comprobante->id, [
            'monto_descuento' => 500.00000,
            'codigo_descuento' => null,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CodigoDescuento>07</CodigoDescuento>', $xml);
    }

    // ==========================================
    // Brecha #3: TotalDesgloseImpuesto con CodigoTarifaIVA
    // ==========================================

    #[Test]
    public function brecha3_desglose_impuesto_agrupa_por_codigo_y_tarifa()
    {
        $comprobante = $this->crearComprobante([
            'total_impuesto' => 3400.00000,
        ]);

        // Línea con IVA 13%
        $this->crearLinea($comprobante->id, [
            'numero_linea' => 1,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => 2600.00000,
        ]);

        // Línea con IVA 4%
        $this->crearLinea($comprobante->id, [
            'numero_linea' => 2,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '03',
            'impuesto_tarifa' => 4.00,
            'impuesto_monto' => 800.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        // Debe generar 2 TotalDesgloseImpuesto (agrupados por código + tarifa)
        $this->assertEquals(2, substr_count($xml, '<TotalDesgloseImpuesto>'));
        $this->assertStringContainsString('<CodigoTarifaIVA>08</CodigoTarifaIVA>', $xml);
        $this->assertStringContainsString('<CodigoTarifaIVA>03</CodigoTarifaIVA>', $xml);
    }

    #[Test]
    public function brecha3_desglose_impuesto_con_tabla_normalizada()
    {
        $comprobante = $this->crearComprobante([
            'total_impuesto' => 3400.00000,
        ]);

        $linea = $this->crearLinea($comprobante->id, [
            'impuesto_monto' => 0, // Sin legacy
        ]);

        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
            'monto' => 2600.00000,
        ]);

        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '02',
            'tarifa' => 10.00,
            'monto' => 800.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        // Debe generar 2 bloques <Impuesto> en la línea
        $this->assertEquals(2, substr_count($xml, '<Impuesto>'));
        $this->assertStringContainsString('<Codigo>01</Codigo>', $xml);
        $this->assertStringContainsString('<Codigo>02</Codigo>', $xml);
    }

    // ==========================================
    // Brecha #4: Ubicación del receptor
    // ==========================================

    #[Test]
    public function brecha4_receptor_ubicacion_se_genera_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'receptor_provincia' => '1',
            'receptor_canton' => '01',
            'receptor_distrito' => '03',
            'receptor_barrio' => '05',
            'receptor_otras_senas' => 'Frente al parque central',
        ]);

        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        // Ubicación del receptor debe estar presente
        $receptores = $dom->getElementsByTagName('Receptor');
        $this->assertEquals(1, $receptores->length);

        $this->assertStringContainsString('<Provincia>1</Provincia>', $xml);
        $this->assertStringContainsString('<Canton>01</Canton>', $xml);
        $this->assertStringContainsString('<Distrito>03</Distrito>', $xml);
        $this->assertStringContainsString('Frente al parque central', $xml);
    }

    // ==========================================
    // Brecha #5: Múltiples impuestos por línea
    // ==========================================

    #[Test]
    public function brecha5_multiples_impuestos_por_linea()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'impuesto_monto' => 0,
        ]);

        // IVA 13%
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
            'monto' => 2600.00000,
        ]);

        // Selectivo de Consumo
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '02',
            'tarifa' => 10.00,
            'monto' => 2000.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertEquals(2, substr_count($xml, '<Impuesto>'));
        $this->assertStringContainsString('<CodigoTarifaIVA>08</CodigoTarifaIVA>', $xml);
    }

    // ==========================================
    // Brecha #6: Múltiples medios de pago
    // ==========================================

    #[Test]
    public function brecha6_multiples_medios_de_pago()
    {
        $comprobante = $this->crearComprobante([
            'total_comprobante' => 22600.00000,
        ]);

        FeMedioPago::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_medio_pago' => '01', // Efectivo
            'total_medio_pago' => 12600.00000,
        ]);

        FeMedioPago::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_medio_pago' => '02', // Tarjeta
            'total_medio_pago' => 10000.00000,
        ]);

        // Forzar carga de relación
        $comprobante->load('mediosPago');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertEquals(2, substr_count($xml, '<MedioPago>'));
        $this->assertStringContainsString('<TipoMedioPago>01</TipoMedioPago>', $xml);
        $this->assertStringContainsString('<TipoMedioPago>02</TipoMedioPago>', $xml);
        $this->assertStringContainsString('<TotalMedioPago>12600.00000</TotalMedioPago>', $xml);
        $this->assertStringContainsString('<TotalMedioPago>10000.00000</TotalMedioPago>', $xml);
    }

    #[Test]
    public function brecha6_medio_pago_otros_con_descripcion()
    {
        $comprobante = $this->crearComprobante();
        FeMedioPago::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_medio_pago' => '99',
            'medio_pago_otros' => 'Bitcoin',
            'total_medio_pago' => 22600.00000,
        ]);

        $comprobante->load('mediosPago');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<MedioPagoOtros>Bitcoin</MedioPagoOtros>', $xml);
    }

    // ==========================================
    // Brecha #7: Emisor Barrio desde DB
    // ==========================================

    #[Test]
    public function brecha7_emisor_barrio_usa_campo_real_de_db()
    {
        $comprobante = $this->crearComprobante();
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // La empresa tiene barrio='03', debe aparecer en XML
        $this->assertStringContainsString('<Barrio>03</Barrio>', $xml);
    }

    // ==========================================
    // Brecha #9: CodigoActividadReceptor
    // ==========================================

    #[Test]
    public function brecha9_codigo_actividad_receptor()
    {
        $comprobante = $this->crearComprobante([
            'codigo_actividad_receptor' => '701100',
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CodigoActividadReceptor>701100</CodigoActividadReceptor>', $xml);
    }

    #[Test]
    public function brecha9_sin_codigo_actividad_receptor_no_genera_tag()
    {
        $comprobante = $this->crearComprobante([
            'codigo_actividad_receptor' => null,
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringNotContainsString('<CodigoActividadReceptor>', $xml);
    }

    // ==========================================
    // Brecha #10: CondicionVentaOtros
    // ==========================================

    #[Test]
    public function brecha10_condicion_venta_otros()
    {
        $comprobante = $this->crearComprobante([
            'condicion_venta' => '99',
            'condicion_venta_otros' => 'Pago parcial con garantía',
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CondicionVenta>99</CondicionVenta>', $xml);
        $this->assertStringContainsString('<CondicionVentaOtros>Pago parcial con garantía</CondicionVentaOtros>', $xml);
    }

    // ==========================================
    // Brecha #11: NombreComercial emisor en XML
    // ==========================================

    #[Test]
    public function brecha11_nombre_comercial_emisor_en_xml()
    {
        $comprobante = $this->crearComprobante();
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<NombreComercial>Test Commerce</NombreComercial>', $xml);
    }

    // ==========================================
    // Brecha #12: NombreComercial receptor
    // ==========================================

    #[Test]
    public function brecha12_nombre_comercial_receptor()
    {
        $comprobante = $this->crearComprobante([
            'receptor_nombre_comercial' => 'Tienda El Sol',
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<NombreComercial>Tienda El Sol</NombreComercial>', $xml);
    }

    // ==========================================
    // Brecha #13: OtrosCargos estructura completa
    // ==========================================

    #[Test]
    public function brecha13_otros_cargos_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'total_otros_cargos' => 5000.00000,
        ]);

        FeOtroCargo::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_documento_oc' => '06',
            'detalle' => 'Cargo por envío',
            'monto_cargo' => 5000.00000,
        ]);

        $comprobante->load('otrosCargos');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<OtrosCargos>', $xml);
        $this->assertStringContainsString('<TipoDocumentoOC>06</TipoDocumentoOC>', $xml);
        $this->assertStringContainsString('<Detalle>Cargo por envío</Detalle>', $xml);
        $this->assertStringContainsString('<MontoCargo>5000.00000</MontoCargo>', $xml);
    }

    // ==========================================
    // Brecha #14: InformacionReferencia como tabla
    // ==========================================

    #[Test]
    public function brecha14_informacion_referencia_desde_tabla()
    {
        $comprobante = $this->crearComprobante(['tipo_documento' => '03']); // Nota crédito
        FeInformacionReferencia::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_doc' => '01',
            'numero' => '52611202531011234567800000000000000000001154489877',
            'fecha_emision' => '2026-03-01',
            'codigo' => '01',
            'razon' => 'Anulación de factura original',
        ]);

        $comprobante->load('informacionReferencia');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<InformacionReferencia>', $xml);
        $this->assertStringContainsString('<TipoDoc>01</TipoDoc>', $xml);
        $this->assertStringContainsString('<Codigo>01</Codigo>', $xml);
        $this->assertStringContainsString('Anulación de factura original', $xml);
    }

    #[Test]
    public function brecha14_referencia_tipo_otro_incluye_campos_extra()
    {
        $comprobante = $this->crearComprobante(['tipo_documento' => '03']);
        FeInformacionReferencia::factory()->tipoOtro()->create([
            'comprobante_id' => $comprobante->id,
        ]);

        $comprobante->load('informacionReferencia');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TipoDocRefOTRO>Documento especial</TipoDocRefOTRO>', $xml);
        $this->assertStringContainsString('<CodigoReferenciaOTRO>Referencia especial</CodigoReferenciaOTRO>', $xml);
    }

    // ==========================================
    // Brecha #15: Múltiples descuentos por línea
    // ==========================================

    #[Test]
    public function brecha15_multiples_descuentos_por_linea()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'monto_descuento' => 0, // Sin legacy
        ]);

        FeLineaDescuento::factory()->create([
            'linea_detalle_id' => $linea->id,
            'orden' => 1,
            'monto_descuento' => 1000.00000,
            'codigo_descuento' => '07',
        ]);

        FeLineaDescuento::factory()->create([
            'linea_detalle_id' => $linea->id,
            'orden' => 2,
            'monto_descuento' => 500.00000,
            'codigo_descuento' => '04',
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertEquals(2, substr_count($xml, '<Descuento>'));
        $this->assertStringContainsString('<CodigoDescuento>07</CodigoDescuento>', $xml);
        $this->assertStringContainsString('<CodigoDescuento>04</CodigoDescuento>', $xml);
    }

    // ==========================================
    // Brecha #19: Totales exonerados en XML
    // ==========================================

    #[Test]
    public function brecha19_totales_exonerados_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'total_servicios_exonerados' => 5000.00000,
            'total_mercancias_exoneradas' => 3000.00000,
            'total_exonerado' => 8000.00000,
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TotalServExonerado>5000.00000</TotalServExonerado>', $xml);
        $this->assertStringContainsString('<TotalMercExonerada>3000.00000</TotalMercExonerada>', $xml);
        $this->assertStringContainsString('<TotalExonerado>8000.00000</TotalExonerado>', $xml);
    }

    // ==========================================
    // Brecha #26: Totales No Sujeto
    // ==========================================

    #[Test]
    public function brecha26_totales_no_sujeto_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'total_servicios_no_sujeto' => 2000.00000,
            'total_mercancias_no_sujeta' => 1500.00000,
            'total_no_sujeto' => 3500.00000,
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TotalServNoSujeto>2000.00000</TotalServNoSujeto>', $xml);
        $this->assertStringContainsString('<TotalMercNoSujeta>1500.00000</TotalMercNoSujeta>', $xml);
        $this->assertStringContainsString('<TotalNoSujeto>3500.00000</TotalNoSujeto>', $xml);
    }

    // ==========================================
    // Brecha #30: TotalIVADevuelto en XML
    // ==========================================

    #[Test]
    public function brecha30_total_iva_devuelto_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'total_iva_devuelto' => 1300.00000,
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TotalIVADevuelto>1300.00000</TotalIVADevuelto>', $xml);
    }

    // ==========================================
    // Brecha #35: Teléfono del receptor
    // ==========================================

    #[Test]
    public function brecha35_telefono_receptor()
    {
        $comprobante = $this->crearComprobante([
            'receptor_telefono_codigo_pais' => '506',
            'receptor_telefono_numero' => '88887777',
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // Receptor tiene teléfono
        $this->assertStringContainsString('<CodigoPais>506</CodigoPais>', $xml);
        $this->assertStringContainsString('<NumTelefono>88887777</NumTelefono>', $xml);
    }

    // ==========================================
    // Test de Impuesto con exoneración (normalizado)
    // ==========================================

    #[Test]
    public function impuesto_normalizado_con_exoneracion()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'impuesto_monto' => 0,
        ]);

        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
            'monto' => 2600.00000,
            'exoneracion_tipo_documento' => '01',
            'exoneracion_numero_documento' => 'EXO-12345678',
            'exoneracion_nombre_institucion' => 'Ministerio de Hacienda',
            'exoneracion_fecha_emision' => '2026-01-15',
            'exoneracion_tarifa_exonerada' => 13.00,
            'exoneracion_monto' => 2600.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Exoneracion>', $xml);
        $this->assertStringContainsString('<TipoDocumentoEX1>01</TipoDocumentoEX1>', $xml);
        $this->assertStringContainsString('<NumeroDocumento>EXO-12345678</NumeroDocumento>', $xml);
        $this->assertStringContainsString('<NombreInstitucion>Ministerio de Hacienda</NombreInstitucion>', $xml);
        $this->assertStringContainsString('<MontoExoneracion>2600.00000</MontoExoneracion>', $xml);
    }

    // ==========================================
    // Brecha #16: ImpuestoAsumidoEmisorFabrica
    // ==========================================

    #[Test]
    public function brecha16_impuesto_asumido_emisor_fabrica()
    {
        $comprobante = $this->crearComprobante([
            'total_imp_asum_emisor_fabrica' => 500.00000,
        ]);
        $this->crearLinea($comprobante->id, [
            'impuesto_asumido_emisor_fabrica' => 500.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<ImpuestoAsumidoEmisorFabrica>500.00000</ImpuestoAsumidoEmisorFabrica>', $xml);
        $this->assertStringContainsString('<TotalImpAsumEmisorFabrica>500.00000</TotalImpAsumEmisorFabrica>', $xml);
    }

    // ==========================================
    // Fallback Legacy — medio pago único
    // ==========================================

    #[Test]
    public function medio_pago_legacy_unico_funciona()
    {
        $comprobante = $this->crearComprobante([
            'medio_pago' => '02',
            'total_comprobante' => 22600.00000,
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // Sin relación mediosPago, usa legacy
        $this->assertStringContainsString('<TipoMedioPago>02</TipoMedioPago>', $xml);
        $this->assertStringContainsString('<TotalMedioPago>22600.00000</TotalMedioPago>', $xml);
    }

    // ==========================================
    // OtrosCargos — XML v4.4 element names
    // ==========================================

    #[Test]
    public function otros_cargos_con_tercero_genera_identificacion_tercero()
    {
        $comprobante = $this->crearComprobante([
            'total_otros_cargos' => 8000.00000,
        ]);

        FeOtroCargo::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_documento_oc' => '04',
            'tercero_tipo_identificacion' => '01',
            'tercero_numero_identificacion' => '109876543',
            'nombre_tercero' => 'Tercero S.A.',
            'detalle' => 'Cargo por tercero',
            'monto_cargo' => 8000.00000,
        ]);

        $comprobante->load('otrosCargos');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TipoDocumentoOC>04</TipoDocumentoOC>', $xml);
        $this->assertStringContainsString('<IdentificacionTercero>', $xml);
        $this->assertStringContainsString('<NombreTercero>Tercero S.A.</NombreTercero>', $xml);
    }

    #[Test]
    public function otros_cargos_tipo_99_incluye_tipo_documento_otros()
    {
        $comprobante = $this->crearComprobante([
            'total_otros_cargos' => 3000.00000,
        ]);

        FeOtroCargo::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_documento_oc' => '99',
            'tipo_documento_otros' => 'Cargo especial por servicio',
            'detalle' => 'Otro cargo',
            'monto_cargo' => 3000.00000,
        ]);

        $comprobante->load('otrosCargos');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TipoDocumentoOC>99</TipoDocumentoOC>', $xml);
        $this->assertStringContainsString('<TipoDocumentoOTROS>Cargo especial por servicio</TipoDocumentoOTROS>', $xml);
    }

    #[Test]
    public function otros_cargos_porcentaje_usa_nombre_v44()
    {
        $comprobante = $this->crearComprobante([
            'total_otros_cargos' => 2000.00000,
        ]);

        FeOtroCargo::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_documento_oc' => '01',
            'detalle' => 'Servicio adicional',
            'porcentaje_oc' => 10.00000,
            'monto_cargo' => 2000.00000,
        ]);

        $comprobante->load('otrosCargos');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // v4.4: PorcentajeOC (no Porcentaje)
        $this->assertStringContainsString('<PorcentajeOC>', $xml);
        $this->assertStringNotContainsString('<Porcentaje>', $xml);
    }

    // ==========================================
    // InformacionReferencia — fecha formateada ISO8601
    // ==========================================

    #[Test]
    public function informacion_referencia_fecha_formato_iso8601()
    {
        $comprobante = $this->crearComprobante(['tipo_documento' => '03']);
        FeInformacionReferencia::factory()->create([
            'comprobante_id' => $comprobante->id,
            'tipo_doc' => '01',
            'numero' => '52611202531011234567800000000000000000001154489877',
            'fecha_emision' => '2026-03-15 10:30:00',
            'codigo' => '01',
            'razon' => 'Corrección',
        ]);

        $comprobante->load('informacionReferencia');
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // Verify fecha_emision is formatted as ISO 8601, not raw object string
        $this->assertMatchesRegularExpression('/<FechaEmision>20\d{2}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $xml);
    }

    // ==========================================
    // Brecha #38: OtroContenido en sección Otros
    // ==========================================

    #[Test]
    public function brecha38_otro_contenido_en_xml()
    {
        $comprobante = $this->crearComprobante([
            'metadata' => [
                'otros' => ['Texto adicional 1'],
                'otros_contenido' => ['Contenido estructurado 1', 'Contenido estructurado 2'],
            ],
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Otros>', $xml);
        $this->assertStringContainsString('<OtroTexto>Texto adicional 1</OtroTexto>', $xml);
        $this->assertStringContainsString('<OtroContenido>Contenido estructurado 1</OtroContenido>', $xml);
        $this->assertStringContainsString('<OtroContenido>Contenido estructurado 2</OtroContenido>', $xml);
    }

    #[Test]
    public function brecha38_solo_otro_contenido_sin_otro_texto()
    {
        $comprobante = $this->crearComprobante([
            'metadata' => [
                'otros_contenido' => ['Solo contenido'],
            ],
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Otros>', $xml);
        $this->assertStringNotContainsString('<OtroTexto>', $xml);
        $this->assertStringContainsString('<OtroContenido>Solo contenido</OtroContenido>', $xml);
    }

    // ==========================================
    // Brecha #18: BaseImponible cálculo correcto
    // ==========================================

    #[Test]
    public function brecha18_base_imponible_usa_valor_explicito_cuando_existe()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'subtotal' => 10000.00000,
            'base_imponible' => 15000.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<BaseImponible>15000.00000</BaseImponible>', $xml);
    }

    #[Test]
    public function brecha18_base_imponible_auto_calcula_subtotal_mas_imp_02_12()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'subtotal' => 10000.00000,
            'base_imponible' => 0,
            'impuesto_monto' => 0,
        ]);

        // IVA (01) - NO suma a BaseImponible
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
            'monto' => 1300.00000,
        ]);

        // Impuesto Selectivo de Consumo (02) - SÍ suma
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '02',
            'tarifa' => 10.00,
            'monto' => 1000.00000,
        ]);

        // Impuesto al Cemento (12) - SÍ suma
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '12',
            'tarifa' => 5.00,
            'monto' => 500.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        // BaseImponible = 10000 + 1000 (02) + 500 (12) = 11500
        $this->assertStringContainsString('<BaseImponible>11500.00000</BaseImponible>', $xml);
    }

    #[Test]
    public function brecha18_base_imponible_sin_imp_especiales_usa_subtotal()
    {
        $comprobante = $this->crearComprobante();
        $linea = $this->crearLinea($comprobante->id, [
            'subtotal' => 10000.00000,
            'base_imponible' => 0,
            'impuesto_monto' => 0,
        ]);

        // Solo IVA (01) - no se suma a BaseImponible
        FeLineaImpuesto::factory()->create([
            'linea_detalle_id' => $linea->id,
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
            'monto' => 1300.00000,
        ]);

        $xml = $this->builder->build($comprobante);

        // BaseImponible = subtotal (10000) sin impuestos adicionales
        $this->assertStringContainsString('<BaseImponible>10000.00000</BaseImponible>', $xml);
    }

    // ==========================================
    // Legacy InformacionReferencia mejorada
    // ==========================================

    #[Test]
    public function legacy_referencia_tipo_doc_99_incluye_tipo_doc_otro()
    {
        $comprobante = $this->crearComprobante([
            'tipo_documento' => '03',
            'metadata' => [
                'documento_referencia' => [
                    'tipo_documento' => '99',
                    'tipo_documento_otro' => 'Factura proforma internacional',
                    'numero' => '001-00001',
                    'fecha_emision' => '2026-04-01T10:00:00-06:00',
                    'codigo' => '01',
                    'razon' => 'Referencia a documento externo',
                ],
            ],
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<TipoDoc>99</TipoDoc>', $xml);
        $this->assertStringContainsString('<TipoDocRefOTRO>Factura proforma internacional</TipoDocRefOTRO>', $xml);
    }

    #[Test]
    public function legacy_referencia_codigo_99_incluye_codigo_referencia_otro()
    {
        $comprobante = $this->crearComprobante([
            'tipo_documento' => '03',
            'metadata' => [
                'documento_referencia' => [
                    'tipo_documento' => '01',
                    'numero' => '001-00002',
                    'fecha_emision' => '2026-04-01',
                    'codigo' => '99',
                    'codigo_referencia_otro' => 'Ajuste por tipo de cambio',
                    'razon' => 'Diferencia cambiaria',
                ],
            ],
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<Codigo>99</Codigo>', $xml);
        $this->assertStringContainsString('<CodigoReferenciaOTRO>Ajuste por tipo de cambio</CodigoReferenciaOTRO>', $xml);
    }

    #[Test]
    public function legacy_referencia_fecha_formateada_iso8601()
    {
        $comprobante = $this->crearComprobante([
            'tipo_documento' => '03',
            'metadata' => [
                'documento_referencia' => [
                    'tipo_documento' => '01',
                    'numero' => '001-00003',
                    'fecha_emision' => '2026-04-01',
                    'codigo' => '01',
                    'razon' => 'Anulación',
                ],
            ],
        ]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // Debe contener formato ISO 8601 (T y timezone)
        $this->assertMatchesRegularExpression(
            '/<FechaEmision>2026-04-01T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}<\/FechaEmision>/',
            $xml
        );
    }

    // ==========================================
    // Brecha #17: Múltiples correos emisor {1,4}
    // ==========================================

    #[Test]
    public function brecha17_un_solo_email_genera_un_correo_electronico()
    {
        $comprobante = $this->crearComprobante(['receptor_email' => null]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CorreoElectronico>test@test.com</CorreoElectronico>', $xml);
        // Solo 1 del emisor (receptor sin email)
        $this->assertSame(1, substr_count($xml, '<CorreoElectronico>'));
    }

    #[Test]
    public function brecha17_multiples_emails_separados_por_coma()
    {
        $this->empresa->update(['email' => 'info@test.com, ventas@test.com, admin@test.com']);
        $comprobante = $this->crearComprobante(['receptor_email' => null]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        $this->assertStringContainsString('<CorreoElectronico>info@test.com</CorreoElectronico>', $xml);
        $this->assertStringContainsString('<CorreoElectronico>ventas@test.com</CorreoElectronico>', $xml);
        $this->assertStringContainsString('<CorreoElectronico>admin@test.com</CorreoElectronico>', $xml);
        $this->assertSame(3, substr_count($xml, '<CorreoElectronico>'));
    }

    #[Test]
    public function brecha17_maximo_4_emails_generados()
    {
        $this->empresa->update(['email' => 'a@t.com;b@t.com;c@t.com;d@t.com;e@t.com']);
        $comprobante = $this->crearComprobante(['receptor_email' => null]);
        $this->crearLinea($comprobante->id);

        $xml = $this->builder->build($comprobante);

        // Máximo 4 según spec Hacienda (5to se descarta)
        $this->assertSame(4, substr_count($xml, '<CorreoElectronico>'));
        $this->assertStringNotContainsString('e@t.com', $xml);
    }
}
