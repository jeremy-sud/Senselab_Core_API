<?php

namespace App\Services;

use App\DTOs\API\ComprobanteElectronicoCreateDTO;
use App\Events\FacturaEmitidaEvent;
use App\Jobs\Hacienda\EnviarComprobanteJob;
use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use App\Models\FeLineaDescuento;
use App\Models\FeMedioPago;
use App\Models\FeInformacionReferencia;
use App\Models\FeOtroCargo;
use App\Services\Hacienda\ClaveNumericaGenerator;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar Comprobantes Electrónicos
 *
 * Encapsula la lógica de negocio para comprobantes electrónicos
 * Fecha de creación: 12 de febrero de 2026
 */
class ComprobanteElectronicoService
{
    /**
     * Crear un nuevo comprobante electrónico (simple)
     */
    public function crear(ComprobanteElectronicoCreateDTO $dto): ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::create($dto->toArray());
    }

    /**
     * Obtener comprobante por ID
     */
    public function obtener(int $comprobanteId): ?ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::find($comprobanteId);
    }

    /**
     * Listar comprobantes con paginación y filtros avanzados
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listarConFiltros(int $empresaId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ComprobanteElectronicoFe::with(['empresa', 'lineasDetalle'])
            ->where('empresa_id', $empresaId);

        if (!empty($filtros['tipo_documento'])) {
            $query->where('tipo_documento', $filtros['tipo_documento']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_emision', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_emision', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['clave'])) {
            $query->where('clave', 'like', '%' . $filtros['clave'] . '%');
        }

        if (!empty($filtros['consecutivo'])) {
            $query->where('consecutivo', 'like', '%' . $filtros['consecutivo'] . '%');
        }

        if (!empty($filtros['receptor_numero_identificacion'])) {
            $query->where('receptor_numero_identificacion', $filtros['receptor_numero_identificacion']);
        }

        $sortBy = $filtros['sort_by'] ?? 'fecha_emision';
        $sortOrder = $filtros['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Listar comprobantes con paginación simple
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return ComprobanteElectronicoFe::paginate($perPage);
    }

    /**
     * Comprobantes por venta
     */
    public function porVenta(int $ventaId): ?ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::where('venta_id', $ventaId)->first();
    }

    /**
     * Crear comprobante completo con líneas, impuestos, descuentos, medios de pago, etc.
     *
     * @param array<string, mixed> $datos
     * @param \App\Models\Empresa $empresa
     * @param int $certificadoId
     * @return ComprobanteElectronicoFe
     */
    public function almacenar(array $datos, \App\Models\Empresa $empresa, int $empresaId, int $certificadoId): ComprobanteElectronicoFe
    {
        return DB::transaction(function () use ($datos, $empresa, $empresaId, $certificadoId) {
            $fechaEmision = $datos['fecha_emision'] ?? Carbon::now();
            $generador = new ClaveNumericaGenerator();
            $clave = $generador->generar(
                $fechaEmision,
                $empresa->num_identificacion_dgt,
                $datos['consecutivo'],
                $datos['situacion'] ?? '1'
            );

            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $empresaId,
                'tipo_documento' => $datos['tipo_documento'],
                'clave' => $clave,
                'consecutivo' => $datos['consecutivo'],
                'fecha_emision' => $fechaEmision,
                'condicion_venta' => $datos['condicion_venta'],
                'condicion_venta_otros' => $datos['condicion_venta_otros'] ?? null,
                'plazo_credito' => $datos['plazo_credito'] ?? null,
                'medio_pago' => $datos['medio_pago'],
                'situacion' => $datos['situacion'] ?? '1',
                'codigo_actividad_receptor' => $datos['codigo_actividad_receptor'] ?? null,
                'receptor_nombre' => $datos['receptor_nombre'],
                'receptor_nombre_comercial' => $datos['receptor_nombre_comercial'] ?? null,
                'receptor_tipo_identificacion' => $datos['receptor_tipo_identificacion'],
                'receptor_numero_identificacion' => $datos['receptor_numero_identificacion'],
                'receptor_email' => $datos['receptor_email'] ?? null,
                'receptor_telefono_codigo_pais' => $datos['receptor_telefono_codigo_pais'] ?? null,
                'receptor_telefono_numero' => $datos['receptor_telefono_numero'] ?? null,
                'receptor_provincia' => $datos['receptor_provincia'] ?? null,
                'receptor_canton' => $datos['receptor_canton'] ?? null,
                'receptor_distrito' => $datos['receptor_distrito'] ?? null,
                'receptor_barrio' => $datos['receptor_barrio'] ?? null,
                'receptor_otras_senas' => $datos['receptor_otras_senas'] ?? null,
                'receptor_otras_senas_extranjero' => $datos['receptor_otras_senas_extranjero'] ?? null,
                'moneda' => $datos['moneda'] ?? $datos['codigo_moneda'] ?? 'CRC',
                'tipo_cambio' => $datos['tipo_cambio'] ?? 1.00000,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'pendiente',
                'intentos_envio' => 0,
            ]);

            [$totalVentaBruta, $totalDescuentos, $totalImpuestos, $totalGravado, $totalExento] = $this->crearLineasDetalle($comprobante, $datos['lineas']);

            $totalVentaNeta = $totalVentaBruta - $totalDescuentos;
            $totalComprobante = $totalVentaNeta + $totalImpuestos;

            $comprobante->update([
                'total_venta' => $totalVentaBruta,
                'total_descuentos' => $totalDescuentos,
                'total_venta_neta' => $totalVentaNeta,
                'total_impuesto' => $totalImpuestos,
                'total_gravado' => $totalGravado,
                'total_exento' => $totalExento,
                'total_comprobante' => $totalComprobante,
            ]);

            $this->crearMediosPago($comprobante, $datos['medios_pago'] ?? []);
            $this->crearInformacionReferencia($comprobante, $datos['informacion_referencia'] ?? []);
            $totalOtrosCargos = $this->crearOtrosCargos($comprobante, $datos['otros_cargos'] ?? []);

            if ($totalOtrosCargos > 0) {
                $comprobante->update([
                    'total_otros_cargos' => $totalOtrosCargos,
                    'total_comprobante' => $totalComprobante + $totalOtrosCargos,
                ]);
            }

            EnviarComprobanteJob::dispatch($comprobante->id, $certificadoId);

            Log::info('Comprobante electrónico creado', [
                'comprobante_id' => $comprobante->id,
                'tipo_documento' => $comprobante->tipo_documento,
                'consecutivo' => $comprobante->consecutivo,
                'total' => $totalComprobante,
            ]);

            return $comprobante->load('lineasDetalle');
        });
    }

    /**
     * Anular comprobante creando nota crédito
     *
     * @param ComprobanteElectronicoFe $comprobanteOriginal (con relaciones cargadas: empresa, lineasDetalle.impuestos, lineasDetalle.descuentos)
     * @param string $razonAnulacion
     * @param int $certificadoId
     * @return ComprobanteElectronicoFe
     */
    public function anular(ComprobanteElectronicoFe $comprobanteOriginal, string $razonAnulacion, int $certificadoId): ComprobanteElectronicoFe
    {
        return DB::transaction(function () use ($comprobanteOriginal, $razonAnulacion, $certificadoId) {
            $consecutivo = $this->generarConsecutivo('03', $comprobanteOriginal->empresa_id);
            $fechaEmision = Carbon::now();
            $generador = new ClaveNumericaGenerator();
            $clave = $generador->generar(
                $fechaEmision,
                $comprobanteOriginal->empresa->num_identificacion_dgt,
                $consecutivo,
                '1'
            );

            $notaCredito = ComprobanteElectronicoFe::create([
                'empresa_id' => $comprobanteOriginal->empresa_id,
                'tipo_documento' => '03',
                'clave' => $clave,
                'consecutivo' => $consecutivo,
                'fecha_emision' => $fechaEmision,
                'condicion_venta' => $comprobanteOriginal->condicion_venta,
                'condicion_venta_otros' => $comprobanteOriginal->condicion_venta_otros,
                'medio_pago' => $comprobanteOriginal->medio_pago,
                'receptor_nombre' => $comprobanteOriginal->receptor_nombre,
                'receptor_nombre_comercial' => $comprobanteOriginal->receptor_nombre_comercial,
                'receptor_tipo_identificacion' => $comprobanteOriginal->receptor_tipo_identificacion,
                'receptor_numero_identificacion' => $comprobanteOriginal->receptor_numero_identificacion,
                'receptor_email' => $comprobanteOriginal->receptor_email,
                'receptor_telefono_codigo_pais' => $comprobanteOriginal->receptor_telefono_codigo_pais,
                'receptor_telefono_numero' => $comprobanteOriginal->receptor_telefono_numero,
                'receptor_provincia' => $comprobanteOriginal->receptor_provincia,
                'receptor_canton' => $comprobanteOriginal->receptor_canton,
                'receptor_distrito' => $comprobanteOriginal->receptor_distrito,
                'receptor_barrio' => $comprobanteOriginal->receptor_barrio,
                'receptor_otras_senas' => $comprobanteOriginal->receptor_otras_senas,
                'receptor_otras_senas_extranjero' => $comprobanteOriginal->receptor_otras_senas_extranjero,
                'moneda' => $comprobanteOriginal->moneda,
                'tipo_cambio' => $comprobanteOriginal->tipo_cambio,
                'total_venta' => $comprobanteOriginal->total_venta,
                'total_descuentos' => $comprobanteOriginal->total_descuentos,
                'total_venta_neta' => $comprobanteOriginal->total_venta_neta,
                'total_impuesto' => $comprobanteOriginal->total_impuesto,
                'total_comprobante' => $comprobanteOriginal->total_comprobante,
                'total_gravado' => $comprobanteOriginal->total_gravado,
                'total_exento' => $comprobanteOriginal->total_exento,
                'estado' => 'pendiente',
                'metadata' => [
                    'documento_referencia' => [
                        'tipo_documento' => $comprobanteOriginal->tipo_documento,
                        'numero' => $comprobanteOriginal->consecutivo,
                        'fecha_emision' => $comprobanteOriginal->fecha_emision->format('Y-m-d\TH:i:sP'),
                        'codigo' => '01',
                        'razon' => $razonAnulacion,
                    ],
                ],
            ]);

            FeInformacionReferencia::create([
                'comprobante_id' => $notaCredito->id,
                'tipo_doc' => $comprobanteOriginal->tipo_documento,
                'numero' => $comprobanteOriginal->consecutivo,
                'fecha_emision' => $comprobanteOriginal->fecha_emision->format('Y-m-d\TH:i:sP'),
                'codigo' => '01',
                'razon' => $razonAnulacion,
            ]);

            $this->copiarLineasDetalle($comprobanteOriginal, $notaCredito);

            EnviarComprobanteJob::dispatch($notaCredito->id, $certificadoId);

            Log::info('Nota crédito creada para anulación', [
                'comprobante_original_id' => $comprobanteOriginal->id,
                'nota_credito_id' => $notaCredito->id,
            ]);

            return $notaCredito->load('lineasDetalle');
        });
    }

    /**
     * Estadísticas de comprobantes por empresa y rango de fechas
     *
     * @return array<string, mixed>
     */
    public function estadisticas(int $empresaId, string|Carbon $fechaDesde, string|Carbon $fechaHasta): array
    {
        return [
            'total_comprobantes' => ComprobanteElectronicoFe::where('empresa_id', $empresaId)
                ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                ->count(),

            'por_estado' => ComprobanteElectronicoFe::where('empresa_id', $empresaId)
                ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->get()
                ->pluck('total', 'estado'),

            'por_tipo' => ComprobanteElectronicoFe::where('empresa_id', $empresaId)
                ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                ->selectRaw('tipo_documento, COUNT(*) as total')
                ->groupBy('tipo_documento')
                ->get()
                ->pluck('total', 'tipo_documento'),

            'total_ventas' => ComprobanteElectronicoFe::where('empresa_id', $empresaId)
                ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                ->where('estado', 'aceptado')
                ->sum('total_comprobante'),
        ];
    }

    /**
     * Cambiar estado de comprobante
     */
    public function cambiarEstado(ComprobanteElectronicoFe $comprobante, string $nuevoEstado): ComprobanteElectronicoFe
    {
        $estadoAnterior = $comprobante->estado;
        $comprobante->estado = $nuevoEstado;
        $comprobante->save();
        $comprobante = $comprobante->fresh() ?? $comprobante;

        $estadosEmision = ['aceptado', 'emitido', 'enviado'];
        if (in_array($nuevoEstado, $estadosEmision, true) && !in_array($estadoAnterior, $estadosEmision, true)) {
            FacturaEmitidaEvent::dispatch($comprobante->empresa_id, [
                'comprobante_id' => $comprobante->id,
                'clave_numerica' => $comprobante->clave_numerica ?? null,
                'tipo_comprobante' => $comprobante->tipo_comprobante ?? null,
                'estado' => $nuevoEstado,
                'venta_id' => $comprobante->venta_id ?? null,
            ]);
        }

        return $comprobante;
    }

    /**
     * Validar clave numérica
     */
    public function validarClaveNumerica(string $clave): bool
    {
        return strlen($clave) === 50;
    }

    /**
     * Generar consecutivo automático
     */
    public function generarConsecutivo(string $tipoDocumento, int $empresaId): string
    {
        $ultimoConsecutivo = ComprobanteElectronicoFe::where('empresa_id', $empresaId)
            ->where('tipo_documento', $tipoDocumento)
            ->orderBy('id', 'desc')
            ->value('consecutivo');

        if (!$ultimoConsecutivo) {
            return '00000000000000000001';
        }

        $numero = intval($ultimoConsecutivo) + 1;
        return str_pad((string) $numero, 20, '0', STR_PAD_LEFT);
    }

    /**
     * Crear líneas de detalle con impuestos y descuentos
     *
     * @param ComprobanteElectronicoFe $comprobante
     * @param array<int, array<string, mixed>> $lineas
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float} [totalVentaBruta, totalDescuentos, totalImpuestos, totalGravado, totalExento]
     */
    private function crearLineasDetalle(ComprobanteElectronicoFe $comprobante, array $lineas): array
    {
        $totalVentaBruta = 0;
        $totalDescuentos = 0;
        $totalImpuestos = 0;
        $totalGravado = 0;
        $totalExento = 0;

        foreach ($lineas as $linea) {
            $impuestoCodigo = null;
            $impuestoCodigoTarifa = null;
            $impuestoTarifa = 0;
            $impuestoMonto = 0;

            if (isset($linea['impuestos']) && is_array($linea['impuestos']) && count($linea['impuestos']) > 0) {
                $primerImpuesto = $linea['impuestos'][0];
                $impuestoCodigo = $primerImpuesto['codigo'] ?? null;
                $impuestoCodigoTarifa = $primerImpuesto['codigo_tarifa'] ?? null;
                $impuestoTarifa = $primerImpuesto['tarifa'] ?? null;
                $impuestoMonto = $primerImpuesto['monto'] ?? null;
            }

            $lineaDetalle = FeLineaDetalle::create([
                'comprobante_id' => $comprobante->id,
                'numero_linea' => $linea['numero_linea'],
                'codigo_tipo' => $linea['codigo_tipo'] ?? '04',
                'codigo' => $linea['codigo'] ?? null,
                'codigo_cabys' => $linea['codigo_cabys'] ?? null,
                'partida_arancelaria' => $linea['partida_arancelaria'] ?? null,
                'cantidad' => $linea['cantidad'],
                'unidad_medida' => $linea['unidad_medida'] ?? 'Sp',
                'unidad_medida_comercial' => $linea['unidad_medida_comercial'] ?? null,
                'detalle' => $linea['detalle'],
                'precio_unitario' => $linea['precio_unitario'],
                'monto_total' => $linea['monto_total'],
                'tipo_transaccion' => $linea['tipo_transaccion'] ?? null,
                'monto_descuento' => $linea['monto_descuento'] ?? 0,
                'naturaleza_descuento' => $linea['naturaleza_descuento'] ?? null,
                'codigo_descuento' => $linea['codigo_descuento'] ?? null,
                'codigo_descuento_otro' => $linea['codigo_descuento_otro'] ?? null,
                'subtotal' => $linea['subtotal'],
                'base_imponible' => $linea['base_imponible'] ?? $linea['subtotal'],
                'monto_total_linea' => $linea['monto_total_linea'],
                'numero_vin_serie' => $linea['numero_vin_serie'] ?? null,
                'registro_medicamento' => $linea['registro_medicamento'] ?? null,
                'forma_farmaceutica' => $linea['forma_farmaceutica'] ?? null,
                'iva_cobrado_fabrica' => $linea['iva_cobrado_fabrica'] ?? null,
                'impuesto_asumido_emisor_fabrica' => $linea['impuesto_asumido_emisor_fabrica'] ?? 0,
                'monto_exportacion' => $linea['monto_exportacion'] ?? null,
                'impuesto_codigo' => $impuestoCodigo,
                'impuesto_codigo_tarifa' => $impuestoCodigoTarifa,
                'impuesto_tarifa' => $impuestoTarifa,
                'impuesto_monto' => $impuestoMonto,
            ]);

            if (isset($linea['impuestos']) && is_array($linea['impuestos'])) {
                foreach ($linea['impuestos'] as $impuesto) {
                    FeLineaImpuesto::create([
                        'linea_detalle_id' => $lineaDetalle->id,
                        'codigo' => $impuesto['codigo'],
                        'codigo_impuesto_otro' => $impuesto['codigo_impuesto_otro'] ?? null,
                        'codigo_tarifa' => $impuesto['codigo_tarifa'] ?? null,
                        'codigo_tarifa_iva' => $impuesto['codigo_tarifa'] ?? null,
                        'tarifa' => $impuesto['tarifa'],
                        'factor_calculo_iva' => $impuesto['factor_calculo_iva'] ?? null,
                        'monto' => $impuesto['monto'],
                        'impuesto_asumido_emisor_fabrica' => $impuesto['impuesto_asumido_emisor_fabrica'] ?? null,
                        'monto_exportacion' => $impuesto['monto_exportacion'] ?? null,
                        'dato_especifico_codigo' => $impuesto['dato_especifico_codigo'] ?? null,
                        'dato_especifico_tipo_gravamen' => $impuesto['dato_especifico_tipo_gravamen'] ?? null,
                        'dato_especifico_unidad_medida' => $impuesto['dato_especifico_unidad_medida'] ?? null,
                        'dato_especifico_cantidad_base' => $impuesto['dato_especifico_cantidad_base'] ?? null,
                        'dato_especifico_monto_gravamen' => $impuesto['dato_especifico_monto_gravamen'] ?? null,
                        'exoneracion_tipo_documento' => $impuesto['exoneracion_tipo_documento'] ?? null,
                        'exoneracion_tipo_documento_otro' => $impuesto['exoneracion_tipo_documento_otro'] ?? null,
                        'exoneracion_numero_documento' => $impuesto['exoneracion_numero_documento'] ?? null,
                        'exoneracion_nombre_institucion' => $impuesto['exoneracion_nombre_institucion'] ?? null,
                        'exoneracion_nombre_institucion_otros' => $impuesto['exoneracion_nombre_institucion_otros'] ?? null,
                        'exoneracion_fecha_emision' => $impuesto['exoneracion_fecha_emision'] ?? null,
                        'exoneracion_porcentaje' => $impuesto['exoneracion_porcentaje_compra'] ?? null,
                        'exoneracion_monto' => $impuesto['exoneracion_monto_impuesto'] ?? null,
                    ]);
                }
            }

            if (isset($linea['descuentos']) && is_array($linea['descuentos'])) {
                foreach ($linea['descuentos'] as $orden => $descuento) {
                    FeLineaDescuento::create([
                        'linea_detalle_id' => $lineaDetalle->id,
                        'orden' => $orden + 1,
                        'monto_descuento' => $descuento['monto_descuento'],
                        'codigo_descuento' => $descuento['codigo_descuento'],
                        'codigo_descuento_otro' => $descuento['codigo_descuento_otro'] ?? null,
                        'naturaleza_descuento' => $descuento['naturaleza_descuento'] ?? null,
                    ]);
                }
            }

            if (isset($linea['impuestos'])) {
                foreach ($linea['impuestos'] as $impuesto) {
                    $totalImpuestos += $impuesto['monto'];
                }
            }

            $totalVentaBruta += $linea['monto_total'];
            $totalDescuentos += $linea['monto_descuento'] ?? 0;

            $subtotalLinea = ($linea['monto_total'] ?? 0) - ($linea['monto_descuento'] ?? 0);
            if (isset($linea['impuestos']) && is_array($linea['impuestos']) && count($linea['impuestos']) > 0) {
                $totalGravado += $subtotalLinea;
            } else {
                $totalExento += $subtotalLinea;
            }
        }

        return [$totalVentaBruta, $totalDescuentos, $totalImpuestos, $totalGravado, $totalExento];
    }

    /**
     * @param array<int, array<string, mixed>> $mediosPago
     */
    private function crearMediosPago(ComprobanteElectronicoFe $comprobante, array $mediosPago): void
    {
        foreach ($mediosPago as $medio) {
            FeMedioPago::create([
                'comprobante_id' => $comprobante->id,
                'tipo_medio_pago' => $medio['tipo_medio_pago'],
                'medio_pago_otros' => $medio['medio_pago_otros'] ?? null,
                'total_medio_pago' => $medio['total_medio_pago'],
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $referencias
     */
    private function crearInformacionReferencia(ComprobanteElectronicoFe $comprobante, array $referencias): void
    {
        foreach ($referencias as $ref) {
            FeInformacionReferencia::create([
                'comprobante_id' => $comprobante->id,
                'tipo_doc' => $ref['tipo_doc'],
                'tipo_doc_otro' => $ref['tipo_doc_otro'] ?? null,
                'numero' => $ref['numero'],
                'fecha_emision' => $ref['fecha_emision'],
                'codigo' => $ref['codigo'],
                'codigo_referencia_otro' => $ref['codigo_referencia_otro'] ?? null,
                'razon' => $ref['razon'],
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $cargos
     */
    private function crearOtrosCargos(ComprobanteElectronicoFe $comprobante, array $cargos): float
    {
        $totalOtrosCargos = 0;
        foreach ($cargos as $cargo) {
            FeOtroCargo::create([
                'comprobante_id' => $comprobante->id,
                'tipo_documento_oc' => $cargo['tipo_documento_oc'],
                'tercero_tipo_identificacion' => $cargo['tercero_tipo_identificacion'] ?? $cargo['tipo_identidad_tercero'] ?? null,
                'tercero_numero_identificacion' => $cargo['tercero_numero_identificacion'] ?? $cargo['numero_identidad_tercero'] ?? null,
                'nombre_tercero' => $cargo['nombre_tercero'] ?? null,
                'detalle' => $cargo['detalle'],
                'porcentaje_oc' => $cargo['porcentaje_oc'] ?? null,
                'monto_cargo' => $cargo['monto_cargo'],
            ]);
            $totalOtrosCargos += (float) $cargo['monto_cargo'];
        }
        return $totalOtrosCargos;
    }

    /**
     * Copiar líneas de detalle (con impuestos y descuentos) de un comprobante a otro
     */
    private function copiarLineasDetalle(ComprobanteElectronicoFe $origen, ComprobanteElectronicoFe $destino): void
    {
        foreach ($origen->lineasDetalle as $linea) {
            $nuevaLinea = FeLineaDetalle::create([
                'comprobante_id' => $destino->id,
                'numero_linea' => $linea->numero_linea,
                'codigo_tipo' => $linea->codigo_tipo,
                'codigo' => $linea->codigo,
                'codigo_cabys' => $linea->codigo_cabys,
                'partida_arancelaria' => $linea->partida_arancelaria,
                'cantidad' => $linea->cantidad,
                'unidad_medida' => $linea->unidad_medida,
                'unidad_medida_comercial' => $linea->unidad_medida_comercial,
                'detalle' => $linea->detalle,
                'precio_unitario' => $linea->precio_unitario,
                'monto_total' => $linea->monto_total,
                'tipo_transaccion' => $linea->tipo_transaccion,
                'monto_descuento' => $linea->monto_descuento,
                'naturaleza_descuento' => $linea->naturaleza_descuento,
                'codigo_descuento' => $linea->codigo_descuento,
                'subtotal' => $linea->subtotal,
                'base_imponible' => $linea->base_imponible,
                'impuesto_codigo' => $linea->impuesto_codigo,
                'impuesto_codigo_tarifa' => $linea->impuesto_codigo_tarifa,
                'impuesto_tarifa' => $linea->impuesto_tarifa,
                'impuesto_monto' => $linea->impuesto_monto,
                'monto_total_linea' => $linea->monto_total_linea,
            ]);

            if ($linea->relationLoaded('impuestos')) {
                foreach ($linea->impuestos as $impuesto) {
                    FeLineaImpuesto::create(array_merge(
                        $impuesto->only($impuesto->getFillable()),
                        ['linea_detalle_id' => $nuevaLinea->id]
                    ));
                }
            }

            if ($linea->relationLoaded('descuentos')) {
                foreach ($linea->descuentos as $descuento) {
                    FeLineaDescuento::create(array_merge(
                        $descuento->only($descuento->getFillable()),
                        ['linea_detalle_id' => $nuevaLinea->id]
                    ));
                }
            }
        }
    }
}
