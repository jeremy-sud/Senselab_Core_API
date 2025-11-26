<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Http\Requests\StoreComprobanteElectronicoRequest;
use App\Jobs\Hacienda\EnviarComprobanteJob;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\ClaveNumericaGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Controlador para gestión de Comprobantes Electrónicos
 * 
 * Endpoints:
 * - POST /api/comprobantes - Crear y enviar comprobante
 * - GET /api/comprobantes - Listar comprobantes
 * - GET /api/comprobantes/{id} - Obtener comprobante
 * - GET /api/comprobantes/{id}/xml - Descargar XML
 * - POST /api/comprobantes/{id}/reenviar - Reenviar comprobante
 * - POST /api/comprobantes/{id}/anular - Anular (crear nota crédito)
 * - GET /api/comprobantes/estadisticas - Estadísticas
 */
class ComprobanteElectronicoController extends Controller
{
    /**
     * Listar comprobantes con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $query = ComprobanteElectronicoFe::with(['empresa', 'lineasDetalle'])
            ->where('empresa_id', $request->user()->empresa_id);

        // Filtros
        if ($request->has('tipo_documento')) {
            $query->where('tipo_documento', $request->tipo_documento);
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->has('clave')) {
            $query->where('clave', 'like', '%' . $request->clave . '%');
        }

        if ($request->has('consecutivo')) {
            $query->where('consecutivo', 'like', '%' . $request->consecutivo . '%');
        }

        if ($request->has('receptor_numero_identificacion')) {
            $query->where('receptor_numero_identificacion', $request->receptor_numero_identificacion);
        }

        // Ordenamiento
        $sortBy = $request->input('sort_by', 'fecha_emision');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->input('per_page', 15);
        $comprobantes = $query->paginate($perPage);

        return response()->json($comprobantes);
    }

    /**
     * Crear y enviar comprobante electrónico
     */
    public function store(StoreComprobanteElectronicoRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generar clave numérica
            $fechaEmision = $request->fecha_emision ?? Carbon::now();
            $empresa = $request->user()->empresa;
            $generador = new ClaveNumericaGenerator();
            $clave = $generador->generar(
                $fechaEmision,
                $empresa->num_identificacion_dgt,
                $request->consecutivo,
                $request->situacion ?? '1'
            );

            // Crear comprobante
            $comprobante = ComprobanteElectronicoFe::create([
                'empresa_id' => $request->user()->empresa_id,
                'tipo_documento' => $request->tipo_documento,
                'clave' => $clave,
                'consecutivo' => $request->consecutivo,
                'fecha_emision' => $fechaEmision,
                'condicion_venta' => $request->condicion_venta,
                'plazo_credito' => $request->plazo_credito,
                'medio_pago' => $request->medio_pago,
                'situacion' => $request->situacion ?? '1',
                
                // Receptor
                'receptor_nombre' => $request->receptor_nombre,
                'receptor_tipo_identificacion' => $request->receptor_tipo_identificacion,
                'receptor_numero_identificacion' => $request->receptor_numero_identificacion,
                'receptor_email' => $request->receptor_email,
                'receptor_telefono' => $request->receptor_telefono,
                'receptor_provincia' => $request->receptor_provincia,
                'receptor_canton' => $request->receptor_canton,
                'receptor_distrito' => $request->receptor_distrito,
                'receptor_barrio' => $request->receptor_barrio,
                'receptor_otras_senas' => $request->receptor_otras_senas,
                
                // Moneda
                'moneda' => $request->moneda ?? $request->codigo_moneda ?? 'CRC',
                'tipo_cambio' => $request->tipo_cambio ?? 1.00000,
                
                // Observaciones
                'observaciones' => $request->observaciones,
                
                // Referencia
                'tipo_documento_referencia' => $request->tipo_documento_referencia,
                'numero_documento_referencia' => $request->numero_documento_referencia,
                'fecha_emision_referencia' => $request->fecha_emision_referencia,
                'codigo_referencia' => $request->codigo_referencia,
                'razon_referencia' => $request->razon_referencia,
                
                // Estado inicial
                'estado' => 'pendiente',
                'intentos_envio' => 0,
            ]);

            // Crear líneas de detalle
            $totalVentaBruta = 0;
            $totalDescuentos = 0;
            $totalImpuestos = 0;

            foreach ($request->lineas as $linea) {
                // Procesar impuestos (tomar el primero si es un array)
                $impuestoCodigo = null;
                $impuestoCodigoTarifa = null;
                $impuestoTarifa = null;
                $impuestoMonto = null;
                
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
                    'codigo' => $linea['codigo'],
                    'cantidad' => $linea['cantidad'],
                    'unidad_medida' => $linea['unidad_medida'] ?? 'Sp',
                    'detalle' => $linea['detalle'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'monto_total' => $linea['monto_total'],
                    'monto_descuento' => $linea['monto_descuento'] ?? 0,
                    'naturaleza_descuento' => $linea['naturaleza_descuento'] ?? null,
                    'subtotal' => $linea['subtotal'],
                    'base_imponible' => $linea['base_imponible'] ?? $linea['subtotal'],
                    'monto_total_linea' => $linea['monto_total_linea'],
                    'impuesto_codigo' => $impuestoCodigo,
                    'impuesto_codigo_tarifa' => $impuestoCodigoTarifa,
                    'impuesto_tarifa' => $impuestoTarifa,
                    'impuesto_monto' => $impuestoMonto,
                ]);

                // Sumar impuestos al total
                if (isset($linea['impuestos'])) {
                    foreach ($linea['impuestos'] as $impuesto) {
                        $totalImpuestos += $impuesto['monto'];
                    }
                }

                $totalVentaBruta += $linea['monto_total'];
                $totalDescuentos += $linea['monto_descuento'] ?? 0;
            }

            // Calcular totales
            $totalVentaNeta = $totalVentaBruta - $totalDescuentos;
            $totalComprobante = $totalVentaNeta + $totalImpuestos;

            // Actualizar totales del comprobante
            $comprobante->update([
                'total_venta' => $totalVentaBruta,
                'total_descuentos' => $totalDescuentos,
                'total_venta_neta' => $totalVentaNeta,
                'total_impuesto' => $totalImpuestos,
                'total_comprobante' => $totalComprobante,
            ]);

            DB::commit();

            // Disparar job asíncrono para envío a Hacienda
            EnviarComprobanteJob::dispatch($comprobante->id, $request->certificado_id);

            Log::info('Comprobante electrónico creado', [
                'comprobante_id' => $comprobante->id,
                'tipo_documento' => $comprobante->tipo_documento,
                'consecutivo' => $comprobante->consecutivo,
                'total' => $totalComprobante,
            ]);

            return response()->json([
                'message' => 'Comprobante creado y enviado a cola de procesamiento',
                'data' => $comprobante->load('lineasDetalle'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear comprobante electrónico', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error al crear comprobante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener comprobante específico
     */
    public function show(int $id): JsonResponse
    {
        $comprobante = ComprobanteElectronicoFe::with(['empresa', 'lineasDetalle'])
            ->findOrFail($id);

        // Verificar autorización (empresa del usuario)
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if ($comprobante->empresa_id !== $user->empresa_id) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        return response()->json($comprobante);
    }

    /**
     * Descargar XML del comprobante
     */
    public function downloadXml(int $id, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $comprobante = ComprobanteElectronicoFe::findOrFail($id);

        // Verificar autorización
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if ($comprobante->empresa_id !== $user->empresa_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $tipo = $request->input('tipo', 'firmado'); // 'original' o 'firmado'
        
        $xml = $tipo === 'original' ? $comprobante->xml_original : $comprobante->xml_firmado;

        if (!$xml) {
            return response()->json([
                'message' => "XML {$tipo} no disponible",
            ], 404);
        }

        $filename = "{$comprobante->clave}_{$tipo}.xml";

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Reenviar comprobante a Hacienda
     */
    public function reenviar(int $id, Request $request): JsonResponse
    {
        $comprobante = ComprobanteElectronicoFe::findOrFail($id);

        // Verificar autorización
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if ($comprobante->empresa_id !== $user->empresa_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Solo se puede reenviar si está en error o rechazado
        if (!in_array($comprobante->estado, ['error', 'rechazado', 'pendiente'])) {
            return response()->json([
                'message' => 'No se puede reenviar un comprobante en estado: ' . $comprobante->estado,
            ], 400);
        }

        $request->validate([
            'certificado_id' => 'required|integer|exists:fe_certificados_digitales,id',
        ]);

        // Reiniciar estado
        $comprobante->update([
            'estado' => 'pendiente',
            'ultimo_error' => null,
        ]);

        // Disparar job
        EnviarComprobanteJob::dispatch($comprobante->id, $request->certificado_id);

        Log::info('Comprobante reenviado', [
            'comprobante_id' => $comprobante->id,
            'clave' => $comprobante->clave,
        ]);

        return response()->json([
            'message' => 'Comprobante enviado a cola de procesamiento',
            'data' => $comprobante,
        ]);
    }

    /**
     * Anular comprobante (crear nota crédito)
     */
    public function anular(int $id, Request $request): JsonResponse
    {
        $comprobanteOriginal = ComprobanteElectronicoFe::with('lineasDetalle')->findOrFail($id);

        // Verificar autorización
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if ($comprobanteOriginal->empresa_id !== $user->empresa_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Solo se puede anular si está aceptado
        if ($comprobanteOriginal->estado !== 'aceptado') {
            return response()->json([
                'message' => 'Solo se pueden anular comprobantes aceptados',
            ], 400);
        }

        $request->validate([
            'razon_anulacion' => 'required|string|max:180',
            'certificado_id' => 'required|integer|exists:fe_certificados_digitales,id',
        ]);

        try {
            DB::beginTransaction();

            // Generar consecutivo y clave para nota crédito
            $consecutivo = $this->generarConsecutivo('03', $comprobanteOriginal->empresa_id);
            $fechaEmision = Carbon::now();
            $generador = new ClaveNumericaGenerator();
            $clave = $generador->generar(
                $fechaEmision,
                $comprobanteOriginal->empresa->num_identificacion_dgt,
                $consecutivo,
                '1'
            );

            // Crear nota crédito
            $notaCredito = ComprobanteElectronicoFe::create([
                'empresa_id' => $comprobanteOriginal->empresa_id,
                'tipo_documento' => '03', // Nota Crédito
                'clave' => $clave,
                'consecutivo' => $consecutivo,
                'fecha_emision' => $fechaEmision,
                'condicion_venta' => $comprobanteOriginal->condicion_venta,
                'medio_pago' => $comprobanteOriginal->medio_pago,
                
                // Receptor (mismo del original)
                'receptor_nombre' => $comprobanteOriginal->receptor_nombre,
                'receptor_tipo_identificacion' => $comprobanteOriginal->receptor_tipo_identificacion,
                'receptor_numero_identificacion' => $comprobanteOriginal->receptor_numero_identificacion,
                'receptor_email' => $comprobanteOriginal->receptor_email,
                
                // Moneda
                'moneda' => $comprobanteOriginal->moneda,
                'tipo_cambio' => $comprobanteOriginal->tipo_cambio,
                
                // Totales (mismo del original)
                'total_venta' => $comprobanteOriginal->total_venta,
                'total_descuentos' => $comprobanteOriginal->total_descuentos,
                'total_venta_neta' => $comprobanteOriginal->total_venta_neta,
                'total_impuesto' => $comprobanteOriginal->total_impuesto,
                'total_comprobante' => $comprobanteOriginal->total_comprobante,
                
                'estado' => 'pendiente',
                
                // Referencia al documento original (en metadata JSON)
                'metadata' => [
                    'documento_referencia' => [
                        'tipo_documento' => $comprobanteOriginal->tipo_documento,
                        'numero' => $comprobanteOriginal->consecutivo,
                        'fecha_emision' => $comprobanteOriginal->fecha_emision->format('Y-m-d\TH:i:sP'),
                        'codigo' => '01', // Anula documento de referencia
                        'razon' => $request->razon_anulacion,
                    ],
                ],
            ]);

            // Copiar líneas de detalle
            foreach ($comprobanteOriginal->lineasDetalle as $linea) {
                FeLineaDetalle::create([
                    'comprobante_id' => $notaCredito->id,
                    'numero_linea' => $linea->numero_linea,
                    'codigo_tipo' => $linea->codigo_tipo,
                    'codigo' => $linea->codigo,
                    'cantidad' => $linea->cantidad,
                    'unidad_medida' => $linea->unidad_medida,
                    'detalle' => $linea->detalle,
                    'precio_unitario' => $linea->precio_unitario,
                    'monto_total' => $linea->monto_total,
                    'monto_descuento' => $linea->monto_descuento,
                    'subtotal' => $linea->subtotal,
                    'base_imponible' => $linea->base_imponible,
                    'impuesto_codigo' => $linea->impuesto_codigo,
                    'impuesto_codigo_tarifa' => $linea->impuesto_codigo_tarifa,
                    'impuesto_tarifa' => $linea->impuesto_tarifa,
                    'impuesto_monto' => $linea->impuesto_monto,
                    'monto_total_linea' => $linea->monto_total_linea,
                ]);
            }

            DB::commit();

            // Enviar nota crédito
            EnviarComprobanteJob::dispatch($notaCredito->id, $request->certificado_id);

            Log::info('Nota crédito creada para anulación', [
                'comprobante_original_id' => $comprobanteOriginal->id,
                'nota_credito_id' => $notaCredito->id,
            ]);

            return response()->json([
                'message' => 'Nota crédito creada y enviada',
                'data' => $notaCredito->load('lineasDetalle'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al crear nota crédito', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al crear nota crédito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estadísticas de comprobantes
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $fechaDesde = $request->input('fecha_desde', Carbon::now()->subMonth());
        $fechaHasta = $request->input('fecha_hasta', Carbon::now());

        $stats = [
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

        return response()->json($stats);
    }

    /**
     * Generar consecutivo automático
     */
    protected function generarConsecutivo(string $tipoDocumento, int $empresaId): string
    {
        $ultimoConsecutivo = ComprobanteElectronicoFe::where('empresa_id', $empresaId)
            ->where('tipo_documento', $tipoDocumento)
            ->orderBy('id', 'desc')
            ->value('consecutivo');

        if (!$ultimoConsecutivo) {
            return '00000000000000000001';
        }

        // Incrementar
        $numero = intval($ultimoConsecutivo) + 1;
        return str_pad($numero, 20, '0', STR_PAD_LEFT);
    }
}
