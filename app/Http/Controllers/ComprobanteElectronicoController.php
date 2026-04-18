<?php

namespace App\Http\Controllers;

use App\Models\ComprobanteElectronicoFe;
use App\Http\Requests\StoreComprobanteElectronicoRequest;
use App\Jobs\Hacienda\EnviarComprobanteJob;
use App\Services\ComprobanteElectronicoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
    public function __construct(
        private readonly ComprobanteElectronicoService $service,
    ) {}

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
        $comprobantes = $this->service->listarConFiltros(
            $request->user()->empresa_id,
            $request->only([
                'tipo_documento', 'estado', 'fecha_desde', 'fecha_hasta',
                'clave', 'consecutivo', 'receptor_numero_identificacion',
                'sort_by', 'sort_order',
            ]),
            (int) $request->input('per_page', 15)
        );

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
            $comprobante = $this->service->almacenar(
                $request->validated(),
                $request->user()->empresa,
                $request->user()->empresa_id,
                $request->certificado_id
            );

            return response()->json([
                'message' => 'Comprobante creado y enviado a cola de procesamiento',
                'data' => $comprobante,
            ], 201);

        } catch (\Exception $e) {
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
            $notaCredito = $this->service->anular(
                $comprobanteOriginal,
                $request->razon_anulacion,
                $request->certificado_id
            );

            return response()->json([
                'message' => 'Nota crédito creada y enviada',
                'data' => $notaCredito,
            ], 201);

        } catch (\Exception $e) {
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

        return response()->json(
            $this->service->estadisticas($empresaId, $fechaDesde, $fechaHasta)
        );
    }
}
