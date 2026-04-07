<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use App\Models\FeLineaDescuento;
use App\Models\FeMedioPago;
use App\Models\FeInformacionReferencia;
use App\Models\FeOtroCargo;
use App\Http\Requests\StoreComprobanteElectronicoRequest;
use App\Jobs\Hacienda\EnviarComprobanteJob;
use App\Services\Hacienda\ClaveNumericaGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Facturación Electrónica',
    description: 'Gestión de comprobantes electrónicos (FE) - Facturas, Notas de Crédito, Tiquetes'
)]
class ComprobanteElectronicoController extends Controller
{
    #[OA\Get(
        path: '/api/comprobantes',
        summary: 'Listar comprobantes electrónicos',
        description: 'Obtener listado paginado de comprobantes con filtros avanzados (tipo, estado, fechas, receptor)',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'tipo_documento',
                in: 'query',
                description: 'Tipo de documento (01=Factura, 02=Nota Débito, 03=Nota Crédito, 04=Tiquete)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['01', '02', '03', '04'])
            ),
            new OA\Parameter(
                name: 'estado',
                in: 'query',
                description: 'Estado del comprobante',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pendiente', 'procesando', 'aceptado', 'rechazado', 'error'])
            ),
            new OA\Parameter(
                name: 'fecha_desde',
                in: 'query',
                description: 'Fecha de emisión desde (YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_hasta',
                in: 'query',
                description: 'Fecha de emisión hasta (YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'clave',
                in: 'query',
                description: 'Búsqueda parcial por clave numérica de 50 dígitos',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'consecutivo',
                in: 'query',
                description: 'Búsqueda parcial por consecutivo',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'receptor_numero_identificacion',
                in: 'query',
                description: 'Número de identificación del receptor',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Campo para ordenamiento',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'fecha_emision')
            ),
            new OA\Parameter(
                name: 'sort_order',
                in: 'query',
                description: 'Orden ascendente o descendente',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Resultados por página',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de comprobantes paginado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'tipo_documento', type: 'string', example: '01'),
                                new OA\Property(property: 'clave', type: 'string', example: '50621011800012345678901234567890123456789012345678'),
                                new OA\Property(property: 'consecutivo', type: 'string', example: '00000000000000000001'),
                                new OA\Property(property: 'fecha_emision', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'estado', type: 'string', example: 'aceptado'),
                                new OA\Property(property: 'receptor_nombre', type: 'string', example: 'Cliente Ejemplo'),
                                new OA\Property(property: 'total_comprobante', type: 'number', format: 'float', example: 11800.00),
                                new OA\Property(property: 'empresa', type: 'object'),
                                new OA\Property(property: 'lineasDetalle', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        )),
                        new OA\Property(property: 'current_page', type: 'integer'),
                        new OA\Property(property: 'per_page', type: 'integer'),
                        new OA\Property(property: 'total', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/comprobantes',
        summary: 'Crear y enviar comprobante electrónico',
        description: 'Crear comprobante (factura, nota crédito, tiquete) y enviarlo automáticamente a Hacienda mediante cola asíncrona',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tipo_documento', 'consecutivo', 'receptor_nombre', 'receptor_tipo_identificacion', 'receptor_numero_identificacion', 'condicion_venta', 'medio_pago', 'lineas', 'certificado_id'],
                properties: [
                    new OA\Property(property: 'tipo_documento', type: 'string', enum: ['01', '02', '03', '04'], example: '01', description: '01=Factura, 02=Nota Débito, 03=Nota Crédito, 04=Tiquete'),
                    new OA\Property(property: 'consecutivo', type: 'string', example: '00000000000000000001', description: 'Consecutivo de 20 dígitos'),
                    new OA\Property(property: 'fecha_emision', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00-06:00', description: 'Fecha ISO 8601 (opcional, usa actual si no se envía)'),
                    new OA\Property(property: 'condicion_venta', type: 'string', enum: ['01', '02', '03', '04'], example: '01', description: '01=Contado, 02=Crédito, 03=Consignación, 04=Apartado'),
                    new OA\Property(property: 'plazo_credito', type: 'integer', example: 30, description: 'Días de crédito (requerido si condicion_venta=02)'),
                    new OA\Property(property: 'medio_pago', type: 'string', enum: ['01', '02', '03', '04', '05'], example: '01', description: '01=Efectivo, 02=Tarjeta, 03=Cheque, 04=Transferencia, 05=Otros'),
                    new OA\Property(property: 'situacion', type: 'string', enum: ['1', '2', '3'], example: '1', description: '1=Normal, 2=Contingencia, 3=Sin Internet'),
                    new OA\Property(property: 'receptor_nombre', type: 'string', example: 'Cliente Ejemplo S.A.'),
                    new OA\Property(property: 'receptor_tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04'], example: '01', description: '01=Física, 02=Jurídica, 03=DIMEX, 04=NITE'),
                    new OA\Property(property: 'receptor_numero_identificacion', type: 'string', example: '304560789'),
                    new OA\Property(property: 'receptor_email', type: 'string', format: 'email', example: 'cliente@example.com'),
                    new OA\Property(property: 'receptor_telefono', type: 'string', example: '88889999'),
                    new OA\Property(property: 'receptor_provincia', type: 'string', example: '1'),
                    new OA\Property(property: 'receptor_canton', type: 'string', example: '01'),
                    new OA\Property(property: 'receptor_distrito', type: 'string', example: '01'),
                    new OA\Property(property: 'receptor_barrio', type: 'string', example: '01'),
                    new OA\Property(property: 'receptor_otras_senas', type: 'string', example: '100m norte de la iglesia'),
                    new OA\Property(property: 'codigo_moneda', type: 'string', example: 'CRC', description: 'USD, EUR, CRC'),
                    new OA\Property(property: 'tipo_cambio', type: 'number', format: 'float', example: 1.00000),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Observaciones adicionales'),
                    new OA\Property(
                        property: 'lineas',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'numero_linea', type: 'integer', example: 1),
                                new OA\Property(property: 'codigo_tipo', type: 'string', example: '04', description: 'Tipo de código del producto'),
                                new OA\Property(property: 'codigo', type: 'string', example: 'PROD001'),
                                new OA\Property(property: 'cantidad', type: 'number', format: 'float', example: 2),
                                new OA\Property(property: 'unidad_medida', type: 'string', example: 'Sp', description: 'Unidad de medida'),
                                new OA\Property(property: 'detalle', type: 'string', example: 'Producto ejemplo'),
                                new OA\Property(property: 'precio_unitario', type: 'number', format: 'float', example: 5000.00),
                                new OA\Property(property: 'monto_total', type: 'number', format: 'float', example: 10000.00),
                                new OA\Property(property: 'monto_descuento', type: 'number', format: 'float', example: 0),
                                new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 10000.00),
                                new OA\Property(property: 'base_imponible', type: 'number', format: 'float', example: 10000.00),
                                new OA\Property(property: 'monto_total_linea', type: 'number', format: 'float', example: 11300.00),
                                new OA\Property(
                                    property: 'impuestos',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'codigo', type: 'string', example: '01', description: '01=IVA'),
                                            new OA\Property(property: 'codigo_tarifa', type: 'string', example: '08', description: 'Código de tarifa Hacienda'),
                                            new OA\Property(property: 'tarifa', type: 'number', format: 'float', example: 13),
                                            new OA\Property(property: 'monto', type: 'number', format: 'float', example: 1300.00),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                    new OA\Property(property: 'certificado_id', type: 'integer', example: 1, description: 'ID del certificado digital a usar para firmar'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Comprobante creado y enviado a cola de procesamiento',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Comprobante creado y enviado a cola de procesamiento'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'tipo_documento', type: 'string', example: '01'),
                                new OA\Property(property: 'clave', type: 'string', example: '50621011800012345678901234567890123456789012345678'),
                                new OA\Property(property: 'consecutivo', type: 'string', example: '00000000000000000001'),
                                new OA\Property(property: 'estado', type: 'string', example: 'pendiente'),
                                new OA\Property(property: 'total_comprobante', type: 'number', format: 'float', example: 11300.00),
                                new OA\Property(property: 'lineasDetalle', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Datos de validación inválidos'),
            new OA\Response(response: 500, description: 'Error al crear comprobante'),
        ]
    )]
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
                'condicion_venta_otros' => $request->condicion_venta_otros,
                'plazo_credito' => $request->plazo_credito,
                'medio_pago' => $request->medio_pago,
                'situacion' => $request->situacion ?? '1',
                'codigo_actividad_receptor' => $request->codigo_actividad_receptor,
                
                // Receptor
                'receptor_nombre' => $request->receptor_nombre,
                'receptor_nombre_comercial' => $request->receptor_nombre_comercial,
                'receptor_tipo_identificacion' => $request->receptor_tipo_identificacion,
                'receptor_numero_identificacion' => $request->receptor_numero_identificacion,
                'receptor_email' => $request->receptor_email,
                'receptor_telefono_codigo_pais' => $request->receptor_telefono_codigo_pais,
                'receptor_telefono_numero' => $request->receptor_telefono_numero,
                'receptor_provincia' => $request->receptor_provincia,
                'receptor_canton' => $request->receptor_canton,
                'receptor_distrito' => $request->receptor_distrito,
                'receptor_barrio' => $request->receptor_barrio,
                'receptor_otras_senas' => $request->receptor_otras_senas,
                'receptor_otras_senas_extranjero' => $request->receptor_otras_senas_extranjero,
                
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
            $totalGravado = 0;
            $totalExento = 0;

            foreach ($request->lineas as $linea) {
                // Procesar impuestos (tomar el primero si es un array para campos legacy)
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
                    'impuesto_asumido_emisor_fabrica' => $linea['impuesto_asumido_emisor_fabrica'] ?? null,
                    'monto_exportacion' => $linea['monto_exportacion'] ?? null,
                    // Campos legacy de impuesto (primer impuesto)
                    'impuesto_codigo' => $impuestoCodigo,
                    'impuesto_codigo_tarifa' => $impuestoCodigoTarifa,
                    'impuesto_tarifa' => $impuestoTarifa,
                    'impuesto_monto' => $impuestoMonto,
                ]);

                // Persistir impuestos normalizados (tabla fe_linea_impuestos)
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

                // Persistir descuentos normalizados (tabla fe_linea_descuentos)
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

                // Sumar impuestos al total
                if (isset($linea['impuestos'])) {
                    foreach ($linea['impuestos'] as $impuesto) {
                        $totalImpuestos += $impuesto['monto'];
                    }
                }

                $totalVentaBruta += $linea['monto_total'];
                $totalDescuentos += $linea['monto_descuento'] ?? 0;

                // Clasificar línea como gravada o exenta
                $subtotalLinea = ($linea['monto_total'] ?? 0) - ($linea['monto_descuento'] ?? 0);
                if (isset($linea['impuestos']) && is_array($linea['impuestos']) && count($linea['impuestos']) > 0) {
                    $totalGravado += $subtotalLinea;
                } else {
                    $totalExento += $subtotalLinea;
                }
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
                'total_gravado' => $totalGravado,
                'total_exento' => $totalExento,
                'total_comprobante' => $totalComprobante,
            ]);

            // Persistir medios de pago normalizados (tabla fe_medios_pago)
            if ($request->has('medios_pago') && is_array($request->medios_pago)) {
                foreach ($request->medios_pago as $medio) {
                    FeMedioPago::create([
                        'comprobante_id' => $comprobante->id,
                        'tipo_medio_pago' => $medio['tipo_medio_pago'],
                        'medio_pago_otros' => $medio['medio_pago_otros'] ?? null,
                        'total_medio_pago' => $medio['total_medio_pago'],
                    ]);
                }
            }

            // Persistir información de referencia normalizada (tabla fe_informacion_referencia)
            if ($request->has('informacion_referencia') && is_array($request->informacion_referencia)) {
                foreach ($request->informacion_referencia as $ref) {
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

            // Persistir otros cargos normalizados (tabla fe_otros_cargos)
            if ($request->has('otros_cargos') && is_array($request->otros_cargos)) {
                $totalOtrosCargos = 0;
                foreach ($request->otros_cargos as $cargo) {
                    FeOtroCargo::create([
                        'comprobante_id' => $comprobante->id,
                        'tipo_documento_oc' => $cargo['tipo_documento_oc'],
                        'tipo_identidad_tercero' => $cargo['tipo_identidad_tercero'] ?? null,
                        'numero_identidad_tercero' => $cargo['numero_identidad_tercero'] ?? null,
                        'nombre_tercero' => $cargo['nombre_tercero'] ?? null,
                        'detalle' => $cargo['detalle'],
                        'porcentaje_oc' => $cargo['porcentaje_oc'] ?? null,
                        'monto_cargo' => $cargo['monto_cargo'],
                    ]);
                    $totalOtrosCargos += (float) $cargo['monto_cargo'];
                }
                // Actualizar total otros cargos y total comprobante
                $comprobante->update([
                    'total_otros_cargos' => $totalOtrosCargos,
                    'total_comprobante' => $totalComprobante + $totalOtrosCargos,
                ]);
            }

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
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error al crear comprobante',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/comprobantes/{id}',
        summary: 'Obtener comprobante específico',
        description: 'Obtener detalles completos de un comprobante incluyendo líneas de detalle y empresa',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del comprobante',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comprobante encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'tipo_documento', type: 'string'),
                        new OA\Property(property: 'clave', type: 'string'),
                        new OA\Property(property: 'consecutivo', type: 'string'),
                        new OA\Property(property: 'estado', type: 'string'),
                        new OA\Property(property: 'fecha_emision', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'total_comprobante', type: 'number', format: 'float'),
                        new OA\Property(property: 'empresa', type: 'object'),
                        new OA\Property(property: 'lineasDetalle', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'No autorizado - comprobante de otra empresa'),
            new OA\Response(response: 404, description: 'Comprobante no encontrado'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/comprobantes/{id}/xml',
        summary: 'Descargar XML del comprobante',
        description: 'Descargar XML original o firmado del comprobante electrónico',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del comprobante',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'tipo',
                in: 'query',
                description: 'Tipo de XML a descargar',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['original', 'firmado'], default: 'firmado')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Archivo XML descargado',
                content: new OA\MediaType(
                    mediaType: 'application/xml',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'XML no disponible'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/comprobantes/{id}/reenviar',
        summary: 'Reenviar comprobante a Hacienda',
        description: 'Reenviar comprobante en estado error, rechazado o pendiente a Hacienda',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del comprobante',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['certificado_id'],
                properties: [
                    new OA\Property(property: 'certificado_id', type: 'integer', example: 1, description: 'ID del certificado digital'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Comprobante enviado a cola de procesamiento',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Comprobante enviado a cola de procesamiento'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'No se puede reenviar comprobante en este estado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Comprobante no encontrado'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/comprobantes/{id}/anular',
        summary: 'Anular comprobante (crear nota crédito)',
        description: 'Crear nota crédito electrónica para anular un comprobante aceptado',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del comprobante a anular',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['razon_anulacion', 'certificado_id'],
                properties: [
                    new OA\Property(property: 'razon_anulacion', type: 'string', maxLength: 180, example: 'Anulación por solicitud del cliente', description: 'Razón de la anulación (máx 180 caracteres)'),
                    new OA\Property(property: 'certificado_id', type: 'integer', example: 1, description: 'ID del certificado digital'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Nota crédito creada y enviada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Nota crédito creada y enviada'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'tipo_documento', type: 'string', example: '03'),
                                new OA\Property(property: 'clave', type: 'string'),
                                new OA\Property(property: 'estado', type: 'string', example: 'pendiente'),
                                new OA\Property(property: 'lineasDetalle', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Solo se pueden anular comprobantes aceptados'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Comprobante no encontrado'),
            new OA\Response(response: 500, description: 'Error al crear nota crédito'),
        ]
    )]
    public function anular(int $id, Request $request): JsonResponse
    {
        $comprobanteOriginal = ComprobanteElectronicoFe::with(['empresa', 'lineasDetalle.impuestos', 'lineasDetalle.descuentos'])->findOrFail($id);

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
                'condicion_venta_otros' => $comprobanteOriginal->condicion_venta_otros,
                'medio_pago' => $comprobanteOriginal->medio_pago,
                
                // Receptor (mismo del original, incluyendo campos v4.4)
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
                
                // Moneda
                'moneda' => $comprobanteOriginal->moneda,
                'tipo_cambio' => $comprobanteOriginal->tipo_cambio,
                
                // Totales (mismo del original)
                'total_venta' => $comprobanteOriginal->total_venta,
                'total_descuentos' => $comprobanteOriginal->total_descuentos,
                'total_venta_neta' => $comprobanteOriginal->total_venta_neta,
                'total_impuesto' => $comprobanteOriginal->total_impuesto,
                'total_comprobante' => $comprobanteOriginal->total_comprobante,
                'total_gravado' => $comprobanteOriginal->total_gravado,
                'total_exento' => $comprobanteOriginal->total_exento,
                
                'estado' => 'pendiente',
                
                // Referencia al documento original (en metadata JSON — para compatibilidad legacy)
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

            // Crear información de referencia normalizada
            FeInformacionReferencia::create([
                'comprobante_id' => $notaCredito->id,
                'tipo_doc' => $comprobanteOriginal->tipo_documento,
                'numero' => $comprobanteOriginal->consecutivo,
                'fecha_emision' => $comprobanteOriginal->fecha_emision->format('Y-m-d\TH:i:sP'),
                'codigo' => '01', // Anula documento de referencia
                'razon' => $request->razon_anulacion,
            ]);

            // Copiar líneas de detalle con campos v4.4
            foreach ($comprobanteOriginal->lineasDetalle as $linea) {
                $nuevaLinea = FeLineaDetalle::create([
                    'comprobante_id' => $notaCredito->id,
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

                // Copiar impuestos normalizados si existen
                if ($linea->relationLoaded('impuestos')) {
                    foreach ($linea->impuestos as $impuesto) {
                        FeLineaImpuesto::create(array_merge(
                            $impuesto->only($impuesto->getFillable()),
                            ['linea_detalle_id' => $nuevaLinea->id]
                        ));
                    }
                }

                // Copiar descuentos normalizados si existen
                if ($linea->relationLoaded('descuentos')) {
                    foreach ($linea->descuentos as $descuento) {
                        FeLineaDescuento::create(array_merge(
                            $descuento->only($descuento->getFillable()),
                            ['linea_detalle_id' => $nuevaLinea->id]
                        ));
                    }
                }
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
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ]);

            return response()->json([
                'message' => 'Error al crear nota crédito',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/comprobantes/estadisticas',
        summary: 'Estadísticas de comprobantes',
        description: 'Obtener estadísticas de comprobantes por estado, tipo y total de ventas en un rango de fechas',
        security: [['sanctum' => []]],
        tags: ['Facturación Electrónica'],
        parameters: [
            new OA\Parameter(
                name: 'fecha_desde',
                in: 'query',
                description: 'Fecha desde (default: hace 1 mes)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_hasta',
                in: 'query',
                description: 'Fecha hasta (default: hoy)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estadísticas de comprobantes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_comprobantes', type: 'integer', example: 150),
                        new OA\Property(
                            property: 'por_estado',
                            type: 'object',
                            example: ['aceptado' => 120, 'pendiente' => 15, 'rechazado' => 10, 'error' => 5]
                        ),
                        new OA\Property(
                            property: 'por_tipo',
                            type: 'object',
                            example: ['01' => 100, '03' => 30, '04' => 20]
                        ),
                        new OA\Property(property: 'total_ventas', type: 'number', format: 'float', example: 2500000.00),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
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
