<?php

namespace App\Services\Hacienda\Xml;

use App\Models\ComprobanteElectronicoFe;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Log;

/**
 * Constructor de XML para Comprobantes Electrónicos v4.3
 * 
 * Genera XMLs conforme al estándar del Ministerio de Hacienda de Costa Rica.
 * Soporta:
 * - Facturas Electrónicas (01)
 * - Notas de Débito (02)
 * - Notas de Crédito (03)
 * - Tiquetes Electrónicos (04)
 */
class XmlComprobanteBuilder
{
    /**
     * Versión del esquema XML
     */
    const VERSION_ESQUEMA = '4.3';

    /**
     * Namespaces XML
     */
    const NAMESPACE_FACTURA = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica';
    const NAMESPACE_NOTA_DEBITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaDebitoElectronica';
    const NAMESPACE_NOTA_CREDITO = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaCreditoElectronica';
    const NAMESPACE_TIQUETE = 'https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/tiqueteElectronico';

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

        // Agregar secciones principales
        $this->agregarClave($root, $comprobante);
        $this->agregarCodigoActividad($root, $comprobante);
        $this->agregarNumeroConsecutivo($root, $comprobante);
        $this->agregarFechaEmision($root, $comprobante);
        $this->agregarEmisor($root, $comprobante);
        $this->agregarReceptor($root, $comprobante);
        $this->agregarCondicionVenta($root, $comprobante);
        $this->agregarPlazoCredito($root, $comprobante);
        $this->agregarMedioPago($root, $comprobante);
        $this->agregarDetalleServicio($root, $comprobante);
        $this->agregarResumenFactura($root, $comprobante);
        $this->agregarInformacionReferencia($root, $comprobante);
        $this->agregarOtros($root, $comprobante);

        $this->doc->appendChild($root);

        // Generar XML string
        $xml = $this->doc->saveXML();

        Log::info('XML generado exitosamente', [
            'tipo_documento' => $comprobante->tipo_documento,
            'clave' => $comprobante->clave,
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

        return $root;
    }

    /**
     * Obtener namespace según tipo de comprobante
     */
    protected function obtenerNamespace(): string
    {
        return match($this->tipoComprobante) {
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
        return match($this->tipoComprobante) {
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
     * Agregar código de actividad económica
     */
    protected function agregarCodigoActividad(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // Obtener código de actividad de la empresa o metadata
        $codigoActividad = $comprobante->metadata['codigo_actividad'] 
            ?? $comprobante->empresa->metadata['codigo_actividad'] 
            ?? '000000';

        $element = $this->doc->createElement('CodigoActividad', $codigoActividad);
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
     * Agregar información del emisor
     */
    protected function agregarEmisor(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $emisor = $this->doc->createElement('Emisor');

        // Nombre
        $nombre = $this->doc->createElement('Nombre', $this->escaparXml($comprobante->empresa->nombre_comercial));
        $emisor->appendChild($nombre);

        // Identificación
        $identificacion = $this->doc->createElement('Identificacion');
        $tipo = $this->doc->createElement('Tipo', $comprobante->empresa->tipo_identificacion ?? '02');
        $numero = $this->doc->createElement('Numero', $comprobante->empresa->numero_identificacion);
        $identificacion->appendChild($tipo);
        $identificacion->appendChild($numero);
        $emisor->appendChild($identificacion);

        // Ubicación
        $ubicacion = $this->doc->createElement('Ubicacion');
        $provincia = $this->doc->createElement('Provincia', $comprobante->empresa->provincia ?? '1');
        $canton = $this->doc->createElement('Canton', $comprobante->empresa->canton ?? '01');
        $distrito = $this->doc->createElement('Distrito', $comprobante->empresa->distrito ?? '01');
        $barrio = $this->doc->createElement('Barrio', $comprobante->empresa->barrio ?? '01');
        $otrasSenas = $this->doc->createElement('OtrasSenas', $this->escaparXml($comprobante->empresa->direccion ?? 'San José'));
        
        $ubicacion->appendChild($provincia);
        $ubicacion->appendChild($canton);
        $ubicacion->appendChild($distrito);
        $ubicacion->appendChild($barrio);
        $ubicacion->appendChild($otrasSenas);
        $emisor->appendChild($ubicacion);

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
     * Agregar medio de pago
     */
    protected function agregarMedioPago(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        $medioPago = $this->doc->createElement('MedioPago', $comprobante->medio_pago);
        $parent->appendChild($medioPago);
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

            // Código del producto/servicio
            $codigo = $this->doc->createElement('Codigo');
            $tipoCodigo = $this->doc->createElement('Tipo', $linea->codigo_tipo);
            $codigoValor = $this->doc->createElement('Codigo', $linea->codigo);
            $codigo->appendChild($tipoCodigo);
            $codigo->appendChild($codigoValor);
            $lineaDetalle->appendChild($codigo);

            // Cantidad
            $cantidad = $this->doc->createElement('Cantidad', $this->formatearDecimal($linea->cantidad));
            $lineaDetalle->appendChild($cantidad);

            // Unidad de medida
            $unidadMedida = $this->doc->createElement('UnidadMedida', $linea->unidad_medida);
            $lineaDetalle->appendChild($unidadMedida);

            // Detalle (descripción)
            $detalle = $this->doc->createElement('Detalle', $this->escaparXml($linea->detalle));
            $lineaDetalle->appendChild($detalle);

            // Precio unitario
            $precioUnitario = $this->doc->createElement('PrecioUnitario', $this->formatearDecimal($linea->precio_unitario));
            $lineaDetalle->appendChild($precioUnitario);

            // Monto total
            $montoTotal = $this->doc->createElement('MontoTotal', $this->formatearDecimal($linea->monto_total));
            $lineaDetalle->appendChild($montoTotal);

            // Descuento (si aplica)
            if ($linea->monto_descuento > 0) {
                $descuento = $this->doc->createElement('Descuento');
                $montoDescuento = $this->doc->createElement('MontoDescuento', $this->formatearDecimal($linea->monto_descuento));
                $naturalezaDescuento = $this->doc->createElement('NaturalezaDescuento', $this->escaparXml($linea->naturaleza_descuento ?? 'Descuento aplicado'));
                $descuento->appendChild($montoDescuento);
                $descuento->appendChild($naturalezaDescuento);
                $lineaDetalle->appendChild($descuento);
            }

            // Subtotal
            $subtotal = $this->doc->createElement('SubTotal', $this->formatearDecimal($linea->subtotal));
            $lineaDetalle->appendChild($subtotal);

            // Impuesto (si aplica)
            if ($linea->impuesto_monto > 0) {
                $impuesto = $this->doc->createElement('Impuesto');
                $codigoImpuesto = $this->doc->createElement('Codigo', $linea->impuesto_codigo ?? '01');
                $codigoTarifa = $this->doc->createElement('CodigoTarifa', $linea->impuesto_codigo_tarifa ?? '08');
                $tarifaImpuesto = $this->doc->createElement('Tarifa', $this->formatearDecimal($linea->impuesto_tarifa));
                $montoImpuesto = $this->doc->createElement('Monto', $this->formatearDecimal($linea->impuesto_monto));
                
                $impuesto->appendChild($codigoImpuesto);
                $impuesto->appendChild($codigoTarifa);
                $impuesto->appendChild($tarifaImpuesto);
                $impuesto->appendChild($montoImpuesto);
                $lineaDetalle->appendChild($impuesto);
            }

            // Monto total de línea
            $montoTotalLinea = $this->doc->createElement('MontoTotalLinea', $this->formatearDecimal($linea->monto_total_linea));
            $lineaDetalle->appendChild($montoTotalLinea);

            $detalleServicio->appendChild($lineaDetalle);
        }

        $parent->appendChild($detalleServicio);
    }

    /**
     * Agregar resumen de factura (totales)
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

        // Total gravado
        $totalGravado = $this->doc->createElement('TotalGravado', $this->formatearDecimal($comprobante->total_gravado));
        $resumen->appendChild($totalGravado);

        // Total exento
        $totalExento = $this->doc->createElement('TotalExento', $this->formatearDecimal($comprobante->total_exento));
        $resumen->appendChild($totalExento);

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

        // Total impuesto
        $totalImpuesto = $this->doc->createElement('TotalImpuesto', $this->formatearDecimal($comprobante->total_impuesto));
        $resumen->appendChild($totalImpuesto);

        // Total comprobante
        $totalComprobante = $this->doc->createElement('TotalComprobante', $this->formatearDecimal($comprobante->total_comprobante));
        $resumen->appendChild($totalComprobante);

        $parent->appendChild($resumen);
    }

    /**
     * Agregar información de referencia (para notas de crédito/débito)
     */
    protected function agregarInformacionReferencia(DOMElement $parent, ComprobanteElectronicoFe $comprobante): void
    {
        // Solo para notas de crédito y débito
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
    protected function formatearDecimal($valor): string
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
