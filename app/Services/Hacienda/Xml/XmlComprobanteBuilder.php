<?php

namespace App\Services\Hacienda\Xml;

use App\Exceptions\HaciendaException;
use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Log;

/**
 * Constructor de XML para Comprobantes Electrónicos v4.4
 *
 * Genera XMLs conforme al estándar del Ministerio de Hacienda de Costa Rica.
 * Actualizado según DGT-R-000-2024 versión 4.4
 *
 * Soporta:
 * - Facturas Electrónicas (01)
 * - Notas de Débito (02)
 * - Notas de Crédito (03)
 * - Tiquetes Electrónicos (04)
 *
 * Cambios principales v4.4:
 * - Nuevo campo ProveedorSistemas (obligatorio)
 * - CodigoActividad renombrado a CodigoActividadEmisor
 * - Nuevos namespaces v4.4
 * - TotalDesgloseImpuesto en ResumenFactura
 *
 * @see DGT-R-000-2024 Resolución de Comprobantes Electrónicos
 */
class XmlComprobanteBuilder
{
    /**
     * Versión del esquema XML
     */
    const VERSION_ESQUEMA = '4.4';

    /**
     * Namespaces XML v4.4
     */
    const NAMESPACE_FACTURA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica';
    const NAMESPACE_NOTA_DEBITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaDebitoElectronica';
    const NAMESPACE_NOTA_CREDITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/notaCreditoElectronica';
    const NAMESPACE_TIQUETE = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/tiqueteElectronico';

    /**
     * Proveedor de sistemas por defecto (debe ser configurado por empresa)
     */
    const DEFAULT_PROVEEDOR_SISTEMAS = 'SISTEMA ERP';

    /**
     * Documento XML
     */
    protected DOMDocument $doc;

    /**
     * Tipo de comprobante actual
     */
    protected string $tipoComprobante;

    /**
     * Construir XML desde modelo ComprobanteElectronicoFe
     *
     * @param ComprobanteElectronicoFe $comprobante Modelo del comprobante
     * @return string XML generado
     */
    public function build(ComprobanteElectronicoFe $comprobante): string
    {
        $this->tipoComprobante = $comprobante->tipo_documento;

        // Crear documento XML
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;
        $this->doc->preserveWhiteSpace = false;

        // Crear elemento raíz según tipo de comprobante
        $root = $this->crearElementoRaiz();

        // Agregar secciones principales (orden según XSD v4.4)
        $this->agregarClave($root, $comprobante);
        $this->agregarCodigoActividadEmisor($root, $comprobante); // Renombrado en v4.4
        $this->agregarCodigoActividadReceptor($root, $comprobante); // Brecha #9
        $this->agregarNumeroConsecutivo($root, $comprobante);
        $this->agregarFechaEmision($root, $comprobante);
        $this->agregarProveedorSistemas($root, $comprobante); // Nuevo en v4.4
        $this->agregarEmisor($root, $comprobante);
        $this->agregarReceptor($root, $comprobante);
        $this->agregarCondicionVenta($root, $comprobante);
        $this->agregarCondicionVentaOtros($root, $comprobante);
        $this->agregarPlazoCredito($root, $comprobante);
        // MedioPago se movió a ResumenFactura en v4.4
        $this->agregarDetalleServicio($root, $comprobante);
        $this->agregarOtrosCargos($root, $comprobante);
        $this->agregarResumenFactura($root, $comprobante); // Incluye MedioPago
        $this->agregarInformacionReferencia($root, $comprobante);
        $this->agregarOtros($root, $comprobante);

        $this->doc->appendChild($root);

        // Generar XML string
        $xml = $this->doc->saveXML();
        if ($xml === false) {
            throw HaciendaException::xmlGeneracionError();
        }

        Log::info('XML v4.4 generado exitosamente', [
            'tipo_documento' => $comprobante->tipo_documento,
            'clave' => $comprobante->clave,
            'version' => self::VERSION_ESQUEMA,
            'xml_length' => strlen($xml),
        ]);

        return $xml;
    }

    /**
     * Crear elemento raíz según tipo de comprobante
     */
    protected function crearElementoRaiz(): DOMElement
    {
        $namespace = $this->obtenerNamespace();
        $nombreRaiz = $this->obtenerNombreRaiz();

        $root = $this->doc->createElementNS($namespace, $nombreRaiz);
        $root->setAttribute('xmlns', $namespace);
        $root->setAttribute('xmlns:ds', 'http://www.w3.org/2000/09/xmldsig#');
        $root->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $root->setAttribute('xmlns:xades', 'http://uri.etsi.org/01903/v1.3.2#'); // Agregado para XAdES

        return $root;
    }

    /**
     * Obtener namespace según tipo de comprobante
     */
    protected function obtenerNamespace(): string
    {
        return match ($this->tipoComprobante) {
            '01' => self::NAMESPACE_FACTURA,
            '02' => self::NAMESPACE_NOTA_DEBITO,
            '03' => self::NAMESPACE_NOTA_CREDITO,
            '04' => self::NAMESPACE_TIQUETE,
            default => throw new \InvalidArgumentException("Tipo de comprobante no soportado: {$this->tipoComprobante}"),
        };
    }

    /**
     * Obtener nombre del elemento raíz
     */
    protected function obtenerNombreRaiz(): string
    {
        return match ($this->tipoComprobante) {
            '01' => 'FacturaElectronica',
            '02' => 'NotaDebitoElectronica',
            '03' => 'NotaCreditoElectronica',
            '04' => 'TiqueteElectronico',
            default => throw new \InvalidArgumentException("Tipo de comprobante no soportado: {$this->tipoComprobante}"),
        };
    }

    /**
     * Agregar clave numérica
     */
    protected function agregarClave(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $clave = $this->doc->createElement('Clave', $comprobante->clave);
        $parent->appendChild($clave);
    }

    /**
     * Agregar código de actividad económica del emisor (v4.4)
     *
     * Nota: Renombrado de CodigoActividad a CodigoActividadEmisor en v4.4
     */
    protected function agregarCodigoActividadEmisor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // Obtener código de actividad de la empresa o metadata
        $codigoActividad = $comprobante->metadata['codigo_actividad']
            ?? $comprobante->empresa->actividad_economica_principal
            ?? $comprobante->empresa->metadata['codigo_actividad']
            ?? '000000';

        // v4.4: Campo renombrado a CodigoActividadEmisor
        $element = $this->doc->createElement('CodigoActividadEmisor', $codigoActividad);
        $parent->appendChild($element);
    }

    /**
     * Agregar número consecutivo
     */
    protected function agregarNumeroConsecutivo(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $consecutivo = $this->doc->createElement('NumeroConsecutivo', $comprobante->consecutivo);
        $parent->appendChild($consecutivo);
    }

    /**
     * Agregar fecha de emisión
     */
    protected function agregarFechaEmision(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $fecha = $comprobante->fecha_emision->toIso8601String();
        $element = $this->doc->createElement('FechaEmision', $fecha);
        $parent->appendChild($element);
    }

    /**
     * Agregar identificación del proveedor del sistema (NUEVO v4.4)
     *
     * Este campo es OBLIGATORIO en v4.4 y identifica al proveedor
     * del sistema de facturación electrónica.
     */
    protected function agregarProveedorSistemas(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // Obtener proveedor del sistema de la empresa o configuración
        $proveedorSistemas = $comprobante->empresa->proveedor_sistemas
            ?? $comprobante->metadata['proveedor_sistemas']
            ?? config('hacienda.proveedor_sistemas', self::DEFAULT_PROVEEDOR_SISTEMAS);

        $element = $this->doc->createElement('ProveedorSistemas', $this->escaparXml($proveedorSistemas));
        $parent->appendChild($element);
    }

    /**
     * Agregar información del emisor
     */
    protected function agregarEmisor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $emisor = $this->doc->createElement('Emisor');

        // Nombre (razón social)
        $nombre = $this->doc->createElement('Nombre', $this->escaparXml($comprobante->empresa->razon_social));
        $emisor->appendChild($nombre);

        // Identificación
        $identificacion = $this->doc->createElement('Identificacion');
        $tipo = $this->doc->createElement('Tipo', $comprobante->empresa->tipo_identificacion ?? '02');
        $numero = $this->doc->createElement('Numero', $comprobante->empresa->num_identificacion_dgt);
        $identificacion->appendChild($tipo);
        $identificacion->appendChild($numero);
        $emisor->appendChild($identificacion);

        // Brecha #22: Registro fiscal 8707 (bebidas alcohólicas)
        if ($comprobante->empresa?->registro_fiscal_8707) {
            $registro = $this->doc->createElement('Registrofiscal8707', $comprobante->empresa->registro_fiscal_8707);
            $emisor->appendChild($registro);
        }

        // Brecha #11: NombreComercial — Campo existe en Empresa pero no se generaba en XML
        if ($comprobante->empresa->nombre_comercial) {
            $nombreComercial = $this->doc->createElement('NombreComercial', $this->escaparXml($comprobante->empresa->nombre_comercial));
            $emisor->appendChild($nombreComercial);
        }

        // Ubicación
        $ubicacion = $this->doc->createElement('Ubicacion');
        $provincia = $this->doc->createElement('Provincia', $comprobante->empresa->provincia ?? '1');
        $canton = $this->doc->createElement('Canton', $comprobante->empresa->canton ?? '01');
        $distrito = $this->doc->createElement('Distrito', $comprobante->empresa->distrito ?? '01');
        // Brecha #7: Usar campo real barrio de DB (ya no hardcodea '01')
        $barrio = $this->doc->createElement('Barrio', $comprobante->empresa->barrio ?? '01');
        $otrasSenas = $this->doc->createElement('OtrasSenas', $this->escaparXml($comprobante->empresa->direccion ?? 'San José'));
        
        $ubicacion->appendChild($provincia);
        $ubicacion->appendChild($canton);
        $ubicacion->appendChild($distrito);
        $ubicacion->appendChild($barrio);
        $ubicacion->appendChild($otrasSenas);
        $emisor->appendChild($ubicacion);

        // Brecha #25: OtrasSenasExtranjero (tipo identificación 05)
        if ($comprobante->empresa->tipo_identificacion === '05' && $comprobante->emisor_otras_senas_extranjero) {
            $otrasExtranjero = $this->doc->createElement('OtrasSenasExtranjero', $this->escaparXml($comprobante->emisor_otras_senas_extranjero));
            $emisor->appendChild($otrasExtranjero);
        }

        // Teléfono
        if ($comprobante->empresa->telefono) {
            $telefono = $this->doc->createElement('Telefono');
            $codigoPais = $this->doc->createElement('CodigoPais', '506');
            $numTelefono = $this->doc->createElement('NumTelefono', preg_replace('/\D/', '', $comprobante->empresa->telefono));
            $telefono->appendChild($codigoPais);
            $telefono->appendChild($numTelefono);
            $emisor->appendChild($telefono);
        }

        // Correo electrónico
        if ($comprobante->empresa->email) {
            $correo = $this->doc->createElement('CorreoElectronico', $this->escaparXml($comprobante->empresa->email));
            $emisor->appendChild($correo);
        }

        $parent->appendChild($emisor);
    }

    /**
     * Agregar información del receptor
     */
    protected function agregarReceptor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // El receptor es opcional en tiquetes electrónicos
        if ($this->tipoComprobante === '04' && !$comprobante->receptor_numero_identificacion) {
            return;
        }

        if (!$comprobante->receptor_numero_identificacion) {
            return;
        }

        $receptor = $this->doc->createElement('Receptor');

        // Nombre
        if ($comprobante->receptor_nombre) {
            $nombre = $this->doc->createElement('Nombre', $this->escaparXml($comprobante->receptor_nombre));
            $receptor->appendChild($nombre);
        }

        // Identificación
        $identificacion = $this->doc->createElement('Identificacion');
        $tipo = $this->doc->createElement('Tipo', $comprobante->receptor_tipo_identificacion ?? '01');
        $numero = $this->doc->createElement('Numero', $comprobante->receptor_numero_identificacion);
        $identificacion->appendChild($tipo);
        $identificacion->appendChild($numero);
        $receptor->appendChild($identificacion);

        // Brecha #12: NombreComercial del receptor
        if ($comprobante->receptor_nombre_comercial) {
            $nombreComercial = $this->doc->createElement('NombreComercial', $this->escaparXml($comprobante->receptor_nombre_comercial));
            $receptor->appendChild($nombreComercial);
        }

        // Brecha #4: Ubicación del receptor
        if ($comprobante->receptor_provincia) {
            $ubicacion = $this->doc->createElement('Ubicacion');
            $provincia = $this->doc->createElement('Provincia', $comprobante->receptor_provincia);
            $canton = $this->doc->createElement('Canton', $comprobante->receptor_canton ?? '01');
            $distrito = $this->doc->createElement('Distrito', $comprobante->receptor_distrito ?? '01');
            $ubicacion->appendChild($provincia);
            $ubicacion->appendChild($canton);
            $ubicacion->appendChild($distrito);

            if ($comprobante->receptor_barrio) {
                $barrio = $this->doc->createElement('Barrio', $comprobante->receptor_barrio);
                $ubicacion->appendChild($barrio);
            }

            $otrasSenas = $this->doc->createElement('OtrasSenas', $this->escaparXml($comprobante->receptor_otras_senas ?? 'No indicado'));
            $ubicacion->appendChild($otrasSenas);
            $receptor->appendChild($ubicacion);
        }

        // Brecha #25: OtrasSenasExtranjero receptor (tipo identificación 05)
        if ($comprobante->receptor_tipo_identificacion === '05' && $comprobante->receptor_otras_senas_extranjero) {
            $otrasExtranjero = $this->doc->createElement('OtrasSenasExtranjero', $this->escaparXml($comprobante->receptor_otras_senas_extranjero));
            $receptor->appendChild($otrasExtranjero);
        }

        // Brecha #35: Teléfono del receptor
        if ($comprobante->receptor_telefono_numero) {
            $telefono = $this->doc->createElement('Telefono');
            $codigoPais = $this->doc->createElement('CodigoPais', $comprobante->receptor_telefono_codigo_pais ?? '506');
            $numTelefono = $this->doc->createElement('NumTelefono', $comprobante->receptor_telefono_numero);
            $telefono->appendChild($codigoPais);
            $telefono->appendChild($numTelefono);
            $receptor->appendChild($telefono);
        }

        // Correo electrónico
        if ($comprobante->receptor_email) {
            $correo = $this->doc->createElement('CorreoElectronico', $this->escaparXml($comprobante->receptor_email));
            $receptor->appendChild($correo);
        }

        $parent->appendChild($receptor);
    }

    /**
     * Agregar condición de venta
     */
    protected function agregarCondicionVenta(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $condicion = $this->doc->createElement('CondicionVenta', $comprobante->condicion_venta);
        $parent->appendChild($condicion);
    }

    /**
     * Agregar plazo de crédito (solo si condición de venta es crédito)
     */
    protected function agregarPlazoCredito(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        if ($comprobante->condicion_venta === '02' && $comprobante->plazo_credito) {
            $plazo = $this->doc->createElement('PlazoCredito', $comprobante->plazo_credito);
            $parent->appendChild($plazo);
        }
    }

    /**
     * Agregar detalle de servicio (líneas del comprobante)
     */
    protected function agregarDetalleServicio(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $detalleServicio = $this->doc->createElement('DetalleServicio');

        foreach ($comprobante->lineasDetalle as $linea) {
            $lineaDetalle = $this->doc->createElement('LineaDetalle');

            // Número de línea
            $numeroLinea = $this->doc->createElement('NumeroLinea', $linea->numero_linea);
            $lineaDetalle->appendChild($numeroLinea);

            // Brecha #20: Partida arancelaria (exportación)
            if ($linea->partida_arancelaria) {
                $partida = $this->doc->createElement('PartidaArancelaria', $linea->partida_arancelaria);
                $lineaDetalle->appendChild($partida);
            }

            // CodigoCABYS
            if ($linea->codigo_cabys) {
                $cabys = $this->doc->createElement('CodigoCABYS', $linea->codigo_cabys);
                $lineaDetalle->appendChild($cabys);
            }

            // Código del producto/servicio
            if ($linea->codigo) {
                $codigo = $this->doc->createElement('CodigoComercial');
                $tipoCodigo = $this->doc->createElement('Tipo', $linea->codigo_tipo ?? '04');
                $codigoValor = $this->doc->createElement('Codigo', $linea->codigo);
                $codigo->appendChild($tipoCodigo);
                $codigo->appendChild($codigoValor);
                $lineaDetalle->appendChild($codigo);
            }

            // Cantidad
            $cantidad = $this->doc->createElement('Cantidad', $this->formatearDecimal($linea->cantidad));
            $lineaDetalle->appendChild($cantidad);

            // Unidad de medida
            $unidadMedida = $this->doc->createElement('UnidadMedida', $linea->unidad_medida);
            $lineaDetalle->appendChild($unidadMedida);

            // Brecha #21: TipoTransaccion
            if ($linea->tipo_transaccion) {
                $tipoTrans = $this->doc->createElement('TipoTransaccion', $linea->tipo_transaccion);
                $lineaDetalle->appendChild($tipoTrans);
            }

            // Unidad medida comercial
            if ($linea->unidad_medida_comercial) {
                $umc = $this->doc->createElement('UnidadMedidaComercial', $this->escaparXml($linea->unidad_medida_comercial));
                $lineaDetalle->appendChild($umc);
            }

            // Detalle (descripción)
            $detalle = $this->doc->createElement('Detalle', $this->escaparXml($linea->detalle));
            $lineaDetalle->appendChild($detalle);

            // Brecha #23: NumeroVINoSerie
            if ($linea->numero_vin_serie) {
                $vin = $this->doc->createElement('NumeroVINoSerie', $linea->numero_vin_serie);
                $lineaDetalle->appendChild($vin);
            }

            // Brecha #24: RegistroMedicamento y FormaFarmaceutica
            if ($linea->registro_medicamento) {
                $regMed = $this->doc->createElement('RegistroMedicamento', $this->escaparXml($linea->registro_medicamento));
                $lineaDetalle->appendChild($regMed);
            }
            if ($linea->forma_farmaceutica) {
                $formaFarm = $this->doc->createElement('FormaFarmaceutica', $linea->forma_farmaceutica);
                $lineaDetalle->appendChild($formaFarm);
            }

            // Precio unitario
            $precioUnitario = $this->doc->createElement('PrecioUnitario', $this->formatearDecimal($linea->precio_unitario));
            $lineaDetalle->appendChild($precioUnitario);

            // Monto total
            $montoTotal = $this->doc->createElement('MontoTotal', $this->formatearDecimal($linea->monto_total));
            $lineaDetalle->appendChild($montoTotal);

            // Descuentos — soporta tabla normalizada y campo legacy
            $this->agregarDescuentosLinea($lineaDetalle, $linea);

            // Subtotal
            $subtotal = $this->doc->createElement('SubTotal', $this->formatearDecimal($linea->subtotal));
            $lineaDetalle->appendChild($subtotal);

            // Brecha #32: IVACobradoFabrica
            if ($linea->iva_cobrado_fabrica) {
                $ivaCobrado = $this->doc->createElement('IVACobradoFabrica', $linea->iva_cobrado_fabrica);
                $lineaDetalle->appendChild($ivaCobrado);
            }

            // BaseImponible (v4.4 - Obligatorio cuando hay impuesto)
            if ($linea->impuesto_monto > 0 || $linea->impuestos->isNotEmpty()) {
                $baseImponible = $linea->base_imponible ?? $linea->subtotal;
                $baseElement = $this->doc->createElement('BaseImponible', $this->formatearDecimal($baseImponible));
                $lineaDetalle->appendChild($baseElement);
            }

            // Brecha #5: Impuestos — soporta tabla normalizada (múltiples) y campo legacy (único)
            $this->agregarImpuestosLinea($lineaDetalle, $linea);

            // Brecha #16: ImpuestoAsumidoEmisorFabrica
            if ($linea->impuesto_asumido_emisor_fabrica > 0) {
                $impAsumido = $this->doc->createElement('ImpuestoAsumidoEmisorFabrica', $this->formatearDecimal($linea->impuesto_asumido_emisor_fabrica));
                $lineaDetalle->appendChild($impAsumido);
            }

            // ImpuestoNeto (v4.4)
            $impuestoNetoTotal = $this->calcularImpuestoNetoLinea($linea);
            if ($impuestoNetoTotal > 0) {
                $impuestoNetoElement = $this->doc->createElement('ImpuestoNeto', $this->formatearDecimal($impuestoNetoTotal));
                $lineaDetalle->appendChild($impuestoNetoElement);
            }

            // Brecha #29: MontoExportacion
            if ($linea->monto_exportacion > 0) {
                $montoExp = $this->doc->createElement('MontoExportacion', $this->formatearDecimal($linea->monto_exportacion));
                $lineaDetalle->appendChild($montoExp);
            }

            // Monto total de línea
            $montoTotalLinea = $this->doc->createElement('MontoTotalLinea', $this->formatearDecimal($linea->monto_total_linea));
            $lineaDetalle->appendChild($montoTotalLinea);

            $detalleServicio->appendChild($lineaDetalle);
        }

        $parent->appendChild($detalleServicio);
    }

    /**
     * Agregar descuentos de una línea (soporta tabla normalizada y campo legacy)
     */
    protected function agregarDescuentosLinea(DOMElement $lineaDetalle, FeLineaDetalle $linea): void
    {
        // Preferir tabla normalizada fe_linea_descuentos (Brecha #15)
        if ($linea->descuentos->isNotEmpty()) {
            foreach ($linea->descuentos->sortBy('orden') as $descuento) {
                $descuentoElement = $this->doc->createElement('Descuento');
                $montoDescuento = $this->doc->createElement('MontoDescuento', $this->formatearDecimal($descuento->monto_descuento));
                $descuentoElement->appendChild($montoDescuento);

                // Brecha #2: CodigoDescuento OBLIGATORIO
                $codigoDesc = $this->doc->createElement('CodigoDescuento', $descuento->codigo_descuento);
                $descuentoElement->appendChild($codigoDesc);

                if ($descuento->codigo_descuento === '99' && $descuento->codigo_descuento_otro) {
                    $codigoOtro = $this->doc->createElement('CodigoDescuentoOTRO', $this->escaparXml($descuento->codigo_descuento_otro));
                    $descuentoElement->appendChild($codigoOtro);
                }

                if ($descuento->naturaleza_descuento) {
                    $naturaleza = $this->doc->createElement('NaturalezaDescuento', $this->escaparXml($descuento->naturaleza_descuento));
                    $descuentoElement->appendChild($naturaleza);
                }

                $lineaDetalle->appendChild($descuentoElement);
            }
            return;
        }

        // Fallback: campo legacy en fe_lineas_detalle
        if ($linea->monto_descuento > 0) {
            $descuentoElement = $this->doc->createElement('Descuento');
            $montoDescuento = $this->doc->createElement('MontoDescuento', $this->formatearDecimal($linea->monto_descuento));
            $descuentoElement->appendChild($montoDescuento);

            // Brecha #2: CodigoDescuento OBLIGATORIO
            $codigoDesc = $this->doc->createElement('CodigoDescuento', $linea->codigo_descuento ?? '07');
            $descuentoElement->appendChild($codigoDesc);

            if ($linea->codigo_descuento === '99' && $linea->codigo_descuento_otro) {
                $codigoOtro = $this->doc->createElement('CodigoDescuentoOTRO', $this->escaparXml($linea->codigo_descuento_otro));
                $descuentoElement->appendChild($codigoOtro);
            }

            if ($linea->naturaleza_descuento) {
                $naturaleza = $this->doc->createElement('NaturalezaDescuento', $this->escaparXml($linea->naturaleza_descuento));
                $descuentoElement->appendChild($naturaleza);
            }

            $lineaDetalle->appendChild($descuentoElement);
        }
    }

    /**
     * Brecha #5: Agregar impuestos de una línea (soporta tabla normalizada y campo legacy)
     */
    protected function agregarImpuestosLinea(DOMElement $lineaDetalle, FeLineaDetalle $linea): void
    {
        // Preferir tabla normalizada fe_linea_impuestos (múltiples impuestos)
        if ($linea->impuestos->isNotEmpty()) {
            foreach ($linea->impuestos as $impuesto) {
                $this->agregarImpuestoElement($lineaDetalle, $impuesto);
            }
            return;
        }

        // Fallback: campo legacy en fe_lineas_detalle (un solo impuesto)
        if ($linea->impuesto_monto > 0) {
            $impuestoElement = $this->doc->createElement('Impuesto');
            $codigoImpuesto = $this->doc->createElement('Codigo', $linea->impuesto_codigo ?? '01');
            $impuestoElement->appendChild($codigoImpuesto);

            if ($linea->impuesto_codigo_tarifa) {
                $codigoTarifa = $this->doc->createElement('CodigoTarifaIVA', $linea->impuesto_codigo_tarifa);
                $impuestoElement->appendChild($codigoTarifa);
            }

            $tarifaImpuesto = $this->doc->createElement('Tarifa', $this->formatearDecimal($linea->impuesto_tarifa));
            $impuestoElement->appendChild($tarifaImpuesto);

            // Brecha #28: FactorCalculoIVA
            if ($linea->factor_calculo_iva) {
                $factor = $this->doc->createElement('FactorCalculoIVA', number_format((float)$linea->factor_calculo_iva, 4, '.', ''));
                $impuestoElement->appendChild($factor);
            }

            $montoImpuesto = $this->doc->createElement('Monto', $this->formatearDecimal($linea->impuesto_monto));
            $impuestoElement->appendChild($montoImpuesto);

            // Exoneración legacy
            if ($linea->exoneracion_monto > 0) {
                $this->agregarExoneracionLegacy($impuestoElement, $linea);
            }

            $lineaDetalle->appendChild($impuestoElement);
        }
    }

    /**
     * Agregar un elemento Impuesto desde el modelo normalizado
     */
    protected function agregarImpuestoElement(DOMElement $parent, FeLineaImpuesto $impuesto): void
    {
        $impuestoElement = $this->doc->createElement('Impuesto');

        $codigoImpuesto = $this->doc->createElement('Codigo', $impuesto->codigo);
        $impuestoElement->appendChild($codigoImpuesto);

        if ($impuesto->codigo === '99' && $impuesto->codigo_impuesto_otro) {
            $codigoOtro = $this->doc->createElement('CodigoImpuestoOTRO', $this->escaparXml($impuesto->codigo_impuesto_otro));
            $impuestoElement->appendChild($codigoOtro);
        }

        if ($impuesto->codigo_tarifa_iva) {
            $codigoTarifa = $this->doc->createElement('CodigoTarifaIVA', $impuesto->codigo_tarifa_iva);
            $impuestoElement->appendChild($codigoTarifa);
        }

        if ($impuesto->tarifa !== null) {
            $tarifa = $this->doc->createElement('Tarifa', $this->formatearDecimal($impuesto->tarifa));
            $impuestoElement->appendChild($tarifa);
        }

        if ($impuesto->factor_calculo_iva) {
            $factor = $this->doc->createElement('FactorCalculoIVA', number_format((float)$impuesto->factor_calculo_iva, 4, '.', ''));
            $impuestoElement->appendChild($factor);
        }

        // Brecha #27: DatosImpuestoEspecifico
        if ($impuesto->tiene_impuesto_especifico) {
            $datosEspecifico = $this->doc->createElement('DatosImpuestoEspecifico');

            if ($impuesto->cantidad_unidad_medida !== null) {
                $cantUM = $this->doc->createElement('CantidadUnidadMedida', number_format((float)$impuesto->cantidad_unidad_medida, 2, '.', ''));
                $datosEspecifico->appendChild($cantUM);
            }
            if ($impuesto->porcentaje !== null) {
                $porc = $this->doc->createElement('Porcentaje', number_format((float)$impuesto->porcentaje, 2, '.', ''));
                $datosEspecifico->appendChild($porc);
            }
            if ($impuesto->proporcion !== null) {
                $prop = $this->doc->createElement('Proporcion', number_format((float)$impuesto->proporcion, 2, '.', ''));
                $datosEspecifico->appendChild($prop);
            }
            if ($impuesto->volumen_unidad_consumo !== null) {
                $vol = $this->doc->createElement('VolumenUnidadConsumo', number_format((float)$impuesto->volumen_unidad_consumo, 2, '.', ''));
                $datosEspecifico->appendChild($vol);
            }
            if ($impuesto->impuesto_unidad !== null) {
                $impUnidad = $this->doc->createElement('ImpuestoUnidad', $this->formatearDecimal($impuesto->impuesto_unidad));
                $datosEspecifico->appendChild($impUnidad);
            }

            $impuestoElement->appendChild($datosEspecifico);
        }

        $monto = $this->doc->createElement('Monto', $this->formatearDecimal($impuesto->monto));
        $impuestoElement->appendChild($monto);

        if ($impuesto->monto_exportacion > 0) {
            $montoExp = $this->doc->createElement('MontoExportacion', $this->formatearDecimal($impuesto->monto_exportacion));
            $impuestoElement->appendChild($montoExp);
        }

        // Exoneración del impuesto
        if ($impuesto->tiene_exoneracion) {
            $exoneracion = $this->doc->createElement('Exoneracion');

            $tipoDocEx = $this->doc->createElement('TipoDocumentoEX1', $impuesto->exoneracion_tipo_documento);
            $exoneracion->appendChild($tipoDocEx);

            if ($impuesto->exoneracion_tipo_documento === '99' && $impuesto->exoneracion_tipo_documento_otro) {
                $tipoOtro = $this->doc->createElement('TipoDocumentoOTRO', $this->escaparXml($impuesto->exoneracion_tipo_documento_otro));
                $exoneracion->appendChild($tipoOtro);
            }

            $numDocEx = $this->doc->createElement('NumeroDocumento', $impuesto->exoneracion_numero_documento);
            $exoneracion->appendChild($numDocEx);

            if ($impuesto->exoneracion_articulo) {
                $articulo = $this->doc->createElement('Articulo', $impuesto->exoneracion_articulo);
                $exoneracion->appendChild($articulo);
            }
            if ($impuesto->exoneracion_inciso) {
                $inciso = $this->doc->createElement('Inciso', $impuesto->exoneracion_inciso);
                $exoneracion->appendChild($inciso);
            }

            $nombreInst = $this->doc->createElement('NombreInstitucion', $this->escaparXml($impuesto->exoneracion_nombre_institucion));
            $exoneracion->appendChild($nombreInst);

            if ($impuesto->exoneracion_nombre_institucion_otros) {
                $nombreOtros = $this->doc->createElement('NombreInstitucionOtros', $this->escaparXml($impuesto->exoneracion_nombre_institucion_otros));
                $exoneracion->appendChild($nombreOtros);
            }

            $fechaExo = $this->doc->createElement('FechaEmisionEX', $impuesto->exoneracion_fecha_emision?->toIso8601String() ?? '');
            $exoneracion->appendChild($fechaExo);

            if ($impuesto->exoneracion_tarifa_exonerada !== null) {
                $tarifaExo = $this->doc->createElement('tarifaexonerada', number_format((float)$impuesto->exoneracion_tarifa_exonerada, 2, '.', ''));
                $exoneracion->appendChild($tarifaExo);
            }

            $montoExo = $this->doc->createElement('MontoExoneracion', $this->formatearDecimal($impuesto->exoneracion_monto));
            $exoneracion->appendChild($montoExo);

            $impuestoElement->appendChild($exoneracion);
        }

        $parent->appendChild($impuestoElement);
    }

    /**
     * Agregar exoneración desde campos legacy en FeLineaDetalle
     */
    protected function agregarExoneracionLegacy(DOMElement $impuestoElement, FeLineaDetalle $linea): void
    {
        $exoneracion = $this->doc->createElement('Exoneracion');

        $tipoDocEx = $this->doc->createElement('TipoDocumentoEX1', $linea->exoneracion_tipo_documento);
        $exoneracion->appendChild($tipoDocEx);

        if ($linea->exoneracion_tipo_documento === '99' && $linea->exoneracion_tipo_documento_otro) {
            $tipoOtro = $this->doc->createElement('TipoDocumentoOTRO', $this->escaparXml($linea->exoneracion_tipo_documento_otro));
            $exoneracion->appendChild($tipoOtro);
        }

        $numDocEx = $this->doc->createElement('NumeroDocumento', $linea->exoneracion_numero_documento);
        $exoneracion->appendChild($numDocEx);

        if ($linea->exoneracion_articulo) {
            $articulo = $this->doc->createElement('Articulo', $linea->exoneracion_articulo);
            $exoneracion->appendChild($articulo);
        }
        if ($linea->exoneracion_inciso) {
            $inciso = $this->doc->createElement('Inciso', $linea->exoneracion_inciso);
            $exoneracion->appendChild($inciso);
        }

        $nombreInst = $this->doc->createElement('NombreInstitucion', $this->escaparXml($linea->exoneracion_nombre_institucion));
        $exoneracion->appendChild($nombreInst);

        if ($linea->exoneracion_nombre_institucion_otros) {
            $nombreOtros = $this->doc->createElement('NombreInstitucionOtros', $this->escaparXml($linea->exoneracion_nombre_institucion_otros));
            $exoneracion->appendChild($nombreOtros);
        }

        $fechaExo = $this->doc->createElement('FechaEmisionEX', $linea->exoneracion_fecha_emision->format('Y-m-d\TH:i:sP'));
        $exoneracion->appendChild($fechaExo);

        if ($linea->exoneracion_tarifa_exonerada !== null) {
            $tarifaExo = $this->doc->createElement('tarifaexonerada', number_format((float)$linea->exoneracion_tarifa_exonerada, 2, '.', ''));
            $exoneracion->appendChild($tarifaExo);
        }

        $montoExo = $this->doc->createElement('MontoExoneracion', $this->formatearDecimal($linea->exoneracion_monto));
        $exoneracion->appendChild($montoExo);

        $impuestoElement->appendChild($exoneracion);
    }

    /**
     * Calcular ImpuestoNeto de una línea (sumando de tabla normalizada o campo legacy)
     */
    protected function calcularImpuestoNetoLinea(FeLineaDetalle $linea): float
    {
        if ($linea->impuestos->isNotEmpty()) {
            $totalMonto = $linea->impuestos->sum('monto');
            $totalExoneracion = $linea->impuestos->sum('exoneracion_monto');
            return (float) $totalMonto - (float) $totalExoneracion;
        }

        return (float) ($linea->impuesto_neto ?? $linea->impuesto_monto ?? 0);
    }

    /**
     * Agregar resumen de factura (totales) v4.4
     *
     * Incluye nuevo campo TotalDesgloseImpuesto para v4.4
     */
    protected function agregarResumenFactura(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $resumen = $this->doc->createElement('ResumenFactura');

        // Código de moneda
        $codigoTipoMoneda = $this->doc->createElement('CodigoTipoMoneda');
        $codigoMoneda = $this->doc->createElement('CodigoMoneda', $comprobante->moneda);
        $tipoCambio = $this->doc->createElement('TipoCambio', $this->formatearDecimal($comprobante->tipo_cambio));
        $codigoTipoMoneda->appendChild($codigoMoneda);
        $codigoTipoMoneda->appendChild($tipoCambio);
        $resumen->appendChild($codigoTipoMoneda);

        // Total servicios gravados
        if ($comprobante->total_servicios_gravados > 0) {
            $element = $this->doc->createElement('TotalServGravados', $this->formatearDecimal($comprobante->total_servicios_gravados));
            $resumen->appendChild($element);
        }

        // Total servicios exentos
        if ($comprobante->total_servicios_exentos > 0) {
            $element = $this->doc->createElement('TotalServExentos', $this->formatearDecimal($comprobante->total_servicios_exentos));
            $resumen->appendChild($element);
        }

        // Brecha #19: Total servicios exonerados
        if ($comprobante->total_servicios_exonerados > 0) {
            $element = $this->doc->createElement('TotalServExonerado', $this->formatearDecimal($comprobante->total_servicios_exonerados));
            $resumen->appendChild($element);
        }

        // Brecha #26: Total servicios no sujeto
        if ($comprobante->total_servicios_no_sujeto > 0) {
            $element = $this->doc->createElement('TotalServNoSujeto', $this->formatearDecimal($comprobante->total_servicios_no_sujeto));
            $resumen->appendChild($element);
        }

        // Total mercancías gravadas
        if ($comprobante->total_mercancias_gravadas > 0) {
            $element = $this->doc->createElement('TotalMercanciasGravadas', $this->formatearDecimal($comprobante->total_mercancias_gravadas));
            $resumen->appendChild($element);
        }

        // Total mercancías exentas
        if ($comprobante->total_mercancias_exentas > 0) {
            $element = $this->doc->createElement('TotalMercanciasExentas', $this->formatearDecimal($comprobante->total_mercancias_exentas));
            $resumen->appendChild($element);
        }

        // Brecha #19: Total mercancías exoneradas
        if ($comprobante->total_mercancias_exoneradas > 0) {
            $element = $this->doc->createElement('TotalMercExonerada', $this->formatearDecimal($comprobante->total_mercancias_exoneradas));
            $resumen->appendChild($element);
        }

        // Brecha #26: Total mercancías no sujeta
        if ($comprobante->total_mercancias_no_sujeta > 0) {
            $element = $this->doc->createElement('TotalMercNoSujeta', $this->formatearDecimal($comprobante->total_mercancias_no_sujeta));
            $resumen->appendChild($element);
        }

        // Total gravado
        $totalGravado = $this->doc->createElement('TotalGravado', $this->formatearDecimal($comprobante->total_gravado));
        $resumen->appendChild($totalGravado);

        // Total exento
        $totalExento = $this->doc->createElement('TotalExento', $this->formatearDecimal($comprobante->total_exento));
        $resumen->appendChild($totalExento);

        // Brecha #19: Total exonerado
        if ($comprobante->total_exonerado > 0) {
            $totalExonerado = $this->doc->createElement('TotalExonerado', $this->formatearDecimal($comprobante->total_exonerado));
            $resumen->appendChild($totalExonerado);
        }

        // Brecha #26: Total no sujeto
        if ($comprobante->total_no_sujeto > 0) {
            $totalNoSujeto = $this->doc->createElement('TotalNoSujeto', $this->formatearDecimal($comprobante->total_no_sujeto));
            $resumen->appendChild($totalNoSujeto);
        }

        // Total venta
        $totalVenta = $this->doc->createElement('TotalVenta', $this->formatearDecimal($comprobante->total_venta));
        $resumen->appendChild($totalVenta);

        // Total descuentos
        if ($comprobante->total_descuentos > 0) {
            $totalDescuentos = $this->doc->createElement('TotalDescuentos', $this->formatearDecimal($comprobante->total_descuentos));
            $resumen->appendChild($totalDescuentos);
        }

        // Total venta neta
        $totalVentaNeta = $this->doc->createElement('TotalVentaNeta', $this->formatearDecimal($comprobante->total_venta_neta));
        $resumen->appendChild($totalVentaNeta);

        // Brecha #3: TotalDesgloseImpuesto — agrupado por Codigo + CodigoTarifaIVA
        $this->agregarTotalDesgloseImpuesto($resumen, $comprobante);

        // Total impuesto
        $totalImpuesto = $this->doc->createElement('TotalImpuesto', $this->formatearDecimal($comprobante->total_impuesto));
        $resumen->appendChild($totalImpuesto);

        // Brecha #16: TotalImpAsumEmisorFabrica
        if ($comprobante->total_imp_asum_emisor_fabrica > 0) {
            $totalImpAsum = $this->doc->createElement('TotalImpAsumEmisorFabrica', $this->formatearDecimal($comprobante->total_imp_asum_emisor_fabrica));
            $resumen->appendChild($totalImpAsum);
        }

        // Brecha #30: TotalIVADevuelto
        if ($comprobante->total_iva_devuelto > 0) {
            $totalIvaDevuelto = $this->doc->createElement('TotalIVADevuelto', $this->formatearDecimal($comprobante->total_iva_devuelto));
            $resumen->appendChild($totalIvaDevuelto);
        }

        // Total otros cargos
        if ($comprobante->total_otros_cargos > 0) {
            $totalOtrosCargos = $this->doc->createElement('TotalOtrosCargos', $this->formatearDecimal($comprobante->total_otros_cargos));
            $resumen->appendChild($totalOtrosCargos);
        }

        // Brecha #6: MedioPago — soporta tabla normalizada (múltiples) y campo legacy (único)
        $this->agregarMedioPagoEnResumen($resumen, $comprobante);

        // Total comprobante
        $totalComprobante = $this->doc->createElement('TotalComprobante', $this->formatearDecimal($comprobante->total_comprobante));
        $resumen->appendChild($totalComprobante);

        $parent->appendChild($resumen);
    }

    /**
     * Brecha #6: MedioPago — soporta tabla normalizada (1-4 medios) y campo legacy (único)
     */
    protected function agregarMedioPagoEnResumen(DOMElement $resumen, ComprobanteElectronicoFe $comprobante): void
    {
        // Preferir tabla normalizada fe_medios_pago
        if ($comprobante->relationLoaded('mediosPago') && $comprobante->mediosPago->isNotEmpty()) {
            foreach ($comprobante->mediosPago as $medio) {
                $medioPago = $this->doc->createElement('MedioPago');

                $tipoElement = $this->doc->createElement('TipoMedioPago', $medio->tipo_medio_pago);
                $medioPago->appendChild($tipoElement);

                // MedioPagoOtros: requerido cuando TipoMedioPago = '99'
                if ($medio->tipo_medio_pago === '99' && $medio->medio_pago_otros) {
                    $otrosElement = $this->doc->createElement('MedioPagoOtros', $this->escaparXml($medio->medio_pago_otros));
                    $medioPago->appendChild($otrosElement);
                }

                $totalElement = $this->doc->createElement('TotalMedioPago', $this->formatearDecimal($medio->total_medio_pago));
                $medioPago->appendChild($totalElement);

                $resumen->appendChild($medioPago);
            }
        } else {
            // Fallback: campo legacy medio_pago (único)
            $medioPago = $this->doc->createElement('MedioPago');

            $tipoMedioPago = $this->doc->createElement('TipoMedioPago', $comprobante->medio_pago);
            $medioPago->appendChild($tipoMedioPago);

            $totalMedioPago = $this->doc->createElement('TotalMedioPago', $this->formatearDecimal($comprobante->total_comprobante));
            $medioPago->appendChild($totalMedioPago);

            $resumen->appendChild($medioPago);
        }
    }

    /**
     * Agregar desglose de impuestos (NUEVO v4.4)
     *
     * Agrupa los impuestos por código y calcula el total por tipo
     */
    protected function agregarTotalDesgloseImpuesto(DOMElement $resumen, ComprobanteElectronicoFe $comprobante): void
    {
        // Brecha #3: Agrupar impuestos por (Codigo, CodigoTarifaIVA) — no solo por Codigo
        $impuestosAgrupados = [];

        foreach ($comprobante->lineasDetalle as $linea) {
            // Preferir tabla normalizada fe_linea_impuestos
            if ($linea->relationLoaded('impuestos') && $linea->impuestos->isNotEmpty()) {
                foreach ($linea->impuestos as $impuesto) {
                    $codigo = $impuesto->codigo ?? '01';
                    $codigoTarifa = $impuesto->codigo_tarifa ?? '';
                    $key = $codigo . '|' . $codigoTarifa;

                    if (!isset($impuestosAgrupados[$key])) {
                        $impuestosAgrupados[$key] = [
                            'codigo' => $codigo,
                            'codigo_tarifa' => $codigoTarifa,
                            'monto' => 0,
                        ];
                    }
                    $impuestosAgrupados[$key]['monto'] += (float) $impuesto->monto;
                }
            } elseif ($linea->impuesto_monto > 0) {
                // Fallback: campos legacy en fe_lineas_detalle
                $codigo = $linea->impuesto_codigo ?? '01';
                $codigoTarifa = $linea->impuesto_codigo_tarifa ?? '';
                $key = $codigo . '|' . $codigoTarifa;

                if (!isset($impuestosAgrupados[$key])) {
                    $impuestosAgrupados[$key] = [
                        'codigo' => $codigo,
                        'codigo_tarifa' => $codigoTarifa,
                        'monto' => 0,
                    ];
                }
                $impuestosAgrupados[$key]['monto'] += (float) $linea->impuesto_monto;
            }
        }

        // Crear elementos TotalDesgloseImpuesto
        foreach ($impuestosAgrupados as $grupo) {
            $desglose = $this->doc->createElement('TotalDesgloseImpuesto');

            $codigoElement = $this->doc->createElement('Codigo', $grupo['codigo']);
            $desglose->appendChild($codigoElement);

            if ($grupo['codigo_tarifa'] !== '') {
                $codigoTarifaElement = $this->doc->createElement('CodigoTarifaIVA', $grupo['codigo_tarifa']);
                $desglose->appendChild($codigoTarifaElement);
            }

            $montoElement = $this->doc->createElement('Monto', $this->formatearDecimal($grupo['monto']));
            $desglose->appendChild($montoElement);

            $resumen->appendChild($desglose);
        }
    }

    /**
     * Brecha #14: InformacionReferencia — soporta tabla normalizada (múltiples) y metadata legacy
     */
    protected function agregarInformacionReferencia(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // Preferir tabla normalizada fe_informacion_referencia
        if ($comprobante->relationLoaded('informacionReferencia') && $comprobante->informacionReferencia->isNotEmpty()) {
            foreach ($comprobante->informacionReferencia as $ref) {
                $infoRefElement = $this->doc->createElement('InformacionReferencia');

                $tipoDoc = $this->doc->createElement('TipoDoc', $ref->tipo_doc);
                $infoRefElement->appendChild($tipoDoc);

                // TipoDocRefOTRO: requerido cuando TipoDoc = '99'
                if ($ref->tipo_doc === '99' && $ref->tipo_doc_otro) {
                    $tipoDocOtro = $this->doc->createElement('TipoDocRefOTRO', $this->escaparXml($ref->tipo_doc_otro));
                    $infoRefElement->appendChild($tipoDocOtro);
                }

                $numero = $this->doc->createElement('Numero', $ref->numero);
                $infoRefElement->appendChild($numero);

                $fechaEmision = $this->doc->createElement('FechaEmision', $ref->fecha_emision);
                $infoRefElement->appendChild($fechaEmision);

                $codigo = $this->doc->createElement('Codigo', $ref->codigo);
                $infoRefElement->appendChild($codigo);

                // CodigoReferenciaOTRO: requerido cuando Codigo = '99'
                if ($ref->codigo === '99' && $ref->codigo_referencia_otro) {
                    $codigoOtro = $this->doc->createElement('CodigoReferenciaOTRO', $this->escaparXml($ref->codigo_referencia_otro));
                    $infoRefElement->appendChild($codigoOtro);
                }

                $razon = $this->doc->createElement('Razon', $this->escaparXml($ref->razon));
                $infoRefElement->appendChild($razon);

                $parent->appendChild($infoRefElement);
            }
            return;
        }

        // Fallback: metadata legacy (solo NC/ND)
        if (!in_array($this->tipoComprobante, ['02', '03'])) {
            return;
        }

        if (!isset($comprobante->metadata['documento_referencia'])) {
            return;
        }

        $infoRef = $comprobante->metadata['documento_referencia'];

        $informacionReferencia = $this->doc->createElement('InformacionReferencia');

        $tipoDoc = $this->doc->createElement('TipoDoc', $infoRef['tipo_documento'] ?? '01');
        $numero = $this->doc->createElement('Numero', $infoRef['numero'] ?? '');
        $fechaEmision = $this->doc->createElement('FechaEmision', $infoRef['fecha_emision'] ?? '');
        $codigo = $this->doc->createElement('Codigo', $infoRef['codigo'] ?? '01');
        $razon = $this->doc->createElement('Razon', $this->escaparXml($infoRef['razon'] ?? ''));

        $informacionReferencia->appendChild($tipoDoc);
        $informacionReferencia->appendChild($numero);
        $informacionReferencia->appendChild($fechaEmision);
        $informacionReferencia->appendChild($codigo);
        $informacionReferencia->appendChild($razon);

        $parent->appendChild($informacionReferencia);
    }

    /**
     * Brecha #9: CodigoActividadReceptor — código de actividad económica del receptor
     */
    protected function agregarCodigoActividadReceptor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        if ($comprobante->codigo_actividad_receptor) {
            $element = $this->doc->createElement('CodigoActividadReceptor', $comprobante->codigo_actividad_receptor);
            $parent->appendChild($element);
        }
    }

    /**
     * Brecha #10: CondicionVentaOtros — descripción cuando condicion_venta = '99'
     */
    protected function agregarCondicionVentaOtros(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        if ($comprobante->condicion_venta === '99' && $comprobante->condicion_venta_otros) {
            $element = $this->doc->createElement('CondicionVentaOtros', $this->escaparXml($comprobante->condicion_venta_otros));
            $parent->appendChild($element);
        }
    }

    /**
     * Brecha #13: OtrosCargos — cargos adicionales desde tabla normalizada
     */
    protected function agregarOtrosCargos(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        if (!$comprobante->relationLoaded('otrosCargos') || $comprobante->otrosCargos->isEmpty()) {
            return;
        }

        foreach ($comprobante->otrosCargos as $cargo) {
            $otrosCargos = $this->doc->createElement('OtrosCargos');

            $tipoDocumento = $this->doc->createElement('TipoDocumento', $cargo->tipo_documento_oc);
            $otrosCargos->appendChild($tipoDocumento);

            // Identificación del tercero (opcional, solo para tipo 12)
            if ($cargo->numero_identidad_tercero) {
                $tipoId = $this->doc->createElement('TipoIdentidadTercero', $cargo->tipo_identidad_tercero ?? '');
                $otrosCargos->appendChild($tipoId);

                $numId = $this->doc->createElement('NumeroIdentidadTercero', $cargo->numero_identidad_tercero);
                $otrosCargos->appendChild($numId);

                if ($cargo->nombre_tercero) {
                    $nombre = $this->doc->createElement('NombreTercero', $this->escaparXml($cargo->nombre_tercero));
                    $otrosCargos->appendChild($nombre);
                }
            }

            $detalle = $this->doc->createElement('Detalle', $this->escaparXml($cargo->detalle));
            $otrosCargos->appendChild($detalle);

            if ($cargo->porcentaje_oc > 0) {
                $porcentaje = $this->doc->createElement('Porcentaje', $this->formatearDecimal($cargo->porcentaje_oc));
                $otrosCargos->appendChild($porcentaje);
            }

            $montoCargo = $this->doc->createElement('MontoCargo', $this->formatearDecimal($cargo->monto_cargo));
            $otrosCargos->appendChild($montoCargo);

            $parent->appendChild($otrosCargos);
        }
    }

    /**
     * Agregar otros elementos opcionales
     */
    protected function agregarOtros(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        if (!isset($comprobante->metadata['otros'])) {
            return;
        }

        $otros = $this->doc->createElement('Otros');
        
        foreach ($comprobante->metadata['otros'] as $otro) {
            $otroTexto = $this->doc->createElement('OtroTexto', $this->escaparXml($otro));
            $otros->appendChild($otroTexto);
        }
        
        $parent->appendChild($otros);
    }

    /**
     * Formatear decimal para XML (máximo 5 decimales)
     */
    protected function formatearDecimal(float|string|int|null $valor): string
    {
        return number_format((float)$valor, 5, '.', '');
    }

    /**
     * Escapar caracteres especiales XML
     */
    protected function escaparXml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
