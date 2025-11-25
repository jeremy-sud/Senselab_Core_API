<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\UpdateComprobanteRecibidoElectronicoRequest;
use App\Http\Requests\ActualizarRespuestaHaciendaRequest;
use App\Http\Resources\ComprobanteRecibidoElectronicoResource;
use App\Models\ComprobanteRecibidoElectronico;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador para Comprobantes Electrónicos Recibidos
 * 
 * Gestiona los comprobantes electrónicos recibidos de proveedores (facturas,
 * notas de crédito, etc.) según normativa DGT de Costa Rica.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ComprobanteRecibidoElectronicoController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['comprobantes-recibidos', 'facturacion-electronica', 'hacienda'];
    protected int $cacheTTL = 1800; // 30min - electronic invoices semi-dynamic
    /**
     * Listar comprobantes recibidos de la empresa
     */
    #[OA\Get(
        path: "/api/comprobantes-recibidos-electronicos",
        summary: "Listar comprobantes electrónicos recibidos",
        description: "Obtiene la lista paginada de comprobantes electrónicos recibidos de proveedores (facturas, notas de crédito, etc.) según normativa DGT.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de comprobantes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "total", type: "integer", example: 45),
                                new OA\Property(property: "per_page", type: "integer", example: 15)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ComprobanteRecibidoElectronico::class);
        
        $empresaId = $request->user()->empresa_id;

        $cacheKey = $this->getCacheKey('index', ['empresa_id' => $empresaId]);

        return $this->cacheQueryIfEnabled($cacheKey, function() use ($empresaId) {
            $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
                ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
                ->orderBy('fecha_recepcion_sistema', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes),
                'meta' => [
                    'current_page' => $comprobantes->currentPage(),
                    'total' => $comprobantes->total(),
                    'per_page' => $comprobantes->perPage()
                ]
            ]);
        });
    }

    /**
     * Registrar nuevo comprobante recibido
     */
    #[OA\Post(
        path: "/api/comprobantes-recibidos-electronicos",
        summary: "Registrar comprobante electrónico recibido",
        description: "Registra un nuevo comprobante electrónico recibido de un proveedor. Almacena XML y datos estructurados.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["proveedor_id", "clave_numerica", "tipo_documento_dgt", "fecha_emision_comprobante", "total_impuesto", "total_comprobante", "xml_contenido"],
                properties: [
                    new OA\Property(property: "proveedor_id", type: "integer", example: 3),
                    new OA\Property(property: "clave_numerica", type: "string", maxLength: 50, example: "50621012400010101234567890123456789012345678"),
                    new OA\Property(property: "consecutivo_receptor", type: "string", maxLength: 20, nullable: true, example: "REC-001"),
                    new OA\Property(property: "tipo_documento_dgt", type: "string", maxLength: 2, example: "01", description: "Código según catálogo DGT: 01=Factura, 02=Nota Débito, 03=Nota Crédito"),
                    new OA\Property(property: "fecha_emision_comprobante", type: "string", format: "date-time", example: "2024-01-15 14:30:00"),
                    new OA\Property(property: "moneda", type: "string", enum: ["CRC", "USD", "EUR"], example: "CRC", description: "Opcional, por defecto CRC"),
                    new OA\Property(property: "total_impuesto", type: "number", format: "decimal", example: 6500.00),
                    new OA\Property(property: "total_comprobante", type: "number", format: "decimal", example: 56500.00),
                    new OA\Property(property: "xml_contenido", type: "string", example: "<?xml version='1.0'..."),
                    new OA\Property(property: "entrada_inventario_id", type: "integer", nullable: true, example: 12)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Comprobante registrado exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 500, description: "Error al registrar"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreComprobanteRecibidoElectronicoRequest $request): JsonResponse
    {
        $this->authorize('create', ComprobanteRecibidoElectronico::class);
        
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();
        try {
            $comprobante = ComprobanteRecibidoElectronico::create([
                'empresa_id' => $empresaId,
                'proveedor_id' => $request->proveedor_id,
                'clave_numerica' => $request->clave_numerica,
                'consecutivo_receptor' => $request->consecutivo_receptor,
                'tipo_documento_dgt' => $request->tipo_documento_dgt,
                'fecha_emision_comprobante' => $request->fecha_emision_comprobante,
                'moneda' => $request->moneda ?? 'CRC',
                'total_impuesto' => $request->total_impuesto,
                'total_comprobante' => $request->total_comprobante,
                'xml_contenido' => $request->xml_contenido,
                'entrada_inventario_id' => $request->entrada_inventario_id,
                'estado_hacienda' => 'Procesando',
                'confirmado_usuario' => 0
            ]);

            DB::commit();
            
            $this->flushCache();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante electrónico registrado exitosamente',
                'data' => new ComprobanteRecibidoElectronicoResource($comprobante->load('proveedor'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar comprobante específico
     */
    #[OA\Get(
        path: "/api/comprobantes-recibidos-electronicos/{id}",
        summary: "Obtener comprobante electrónico",
        description: "Obtiene el detalle completo de un comprobante electrónico recibido con proveedor, entrada de inventario y usuario que confirmó.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Comprobante obtenido exitosamente"),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->with(['proveedor', 'entradaInventario', 'usuarioConfirmacion'])
            ->findOrFail($id);
        
        $this->authorize('view', $comprobante);

        return response()->json([
            'success' => true,
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante)
        ]);
    }

    /**
     * Actualizar comprobante recibido
     */
    #[OA\Put(
        path: "/api/comprobantes-recibidos-electronicos/{id}",
        summary: "Actualizar comprobante electrónico",
        description: "Actualiza datos del comprobante. No permite modificar comprobantes ya confirmados.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "proveedor_id", type: "integer", example: 3),
                    new OA\Property(property: "consecutivo_receptor", type: "string", maxLength: 20, example: "REC-001"),
                    new OA\Property(property: "entrada_inventario_id", type: "integer", nullable: true, example: 12)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Comprobante actualizado exitosamente"),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un comprobante ya confirmado"),
            new OA\Response(response: 500, description: "Error al actualizar"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateComprobanteRecibidoElectronicoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);
        
        $this->authorize('update', $comprobante);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un comprobante ya confirmado'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $comprobante->update($request->only([
                'proveedor_id',
                'consecutivo_receptor',
                'entrada_inventario_id'
            ]));

            DB::commit();
            
            $this->flushCache();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante actualizado exitosamente',
                'data' => new ComprobanteRecibidoElectronicoResource($comprobante->load('proveedor'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el comprobante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar comprobante
     */
    #[OA\Delete(
        path: "/api/comprobantes-recibidos-electronicos/{id}",
        summary: "Eliminar comprobante electrónico",
        description: "Elimina un comprobante electrónico. No permite eliminar comprobantes ya confirmados.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Comprobante eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Comprobante eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un comprobante ya confirmado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);
        
        $this->authorize('delete', $comprobante);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un comprobante ya confirmado'
            ], 422);
        }

        $comprobante->delete();
        
        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Comprobante eliminado exitosamente'
        ]);
    }

    /**
     * Confirmar/Aceptar comprobante por usuario
     */
    #[OA\Post(
        path: "/api/comprobantes-recibidos-electronicos/{id}/confirmar",
        summary: "Confirmar comprobante",
        description: "Marca el comprobante como confirmado/aceptado por el usuario receptor. Registra fecha y usuario que confirmó.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Comprobante confirmado exitosamente"),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 422, description: "El comprobante ya fue confirmado anteriormente"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function confirmar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $usuarioId = $request->user()->id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        if ($comprobante->confirmado_usuario == 1) {
            return response()->json([
                'success' => false,
                'message' => 'El comprobante ya fue confirmado anteriormente'
            ], 422);
        }

        $comprobante->update([
            'confirmado_usuario' => 1,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante confirmado exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))
        ]);
    }

    /**
     * Rechazar comprobante por usuario
     */
    #[OA\Post(
        path: "/api/comprobantes-recibidos-electronicos/{id}/rechazar",
        summary: "Rechazar comprobante",
        description: "Marca el comprobante como rechazado por el usuario receptor. Registra fecha y usuario que rechazó.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Comprobante rechazado exitosamente"),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function rechazar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $usuarioId = $request->user()->id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        $comprobante->update([
            'confirmado_usuario' => 2,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comprobante rechazado exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante->fresh(['proveedor', 'usuarioConfirmacion']))
        ]);
    }

    /**
     * Obtener comprobantes por proveedor
     */
    #[OA\Get(
        path: "/api/comprobantes-recibidos-electronicos/proveedor/{proveedorId}",
        summary: "Comprobantes por proveedor",
        description: "Obtiene todos los comprobantes electrónicos recibidos de un proveedor específico.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "proveedorId",
                in: "path",
                description: "ID del proveedor",
                required: true,
                schema: new OA\Schema(type: "integer", example: 3)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de comprobantes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object")),
                        new OA\Property(
                            property: "meta",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "total", type: "integer", example: 23)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function porProveedor(Request $request, int $proveedorId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->with(['proveedor', 'entradaInventario'])
            ->orderBy('fecha_emision_comprobante', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes),
            'meta' => [
                'current_page' => $comprobantes->currentPage(),
                'total' => $comprobantes->total()
            ]
        ]);
    }

    /**
     * Obtener comprobantes pendientes de confirmar
     */
    #[OA\Get(
        path: "/api/comprobantes-recibidos-electronicos/pendientes/list",
        summary: "Comprobantes pendientes",
        description: "Obtiene todos los comprobantes electrónicos que aún no han sido confirmados ni rechazados por el usuario.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de comprobantes pendientes obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function pendientes(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobantes = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('confirmado_usuario', 0)
            ->with(['proveedor'])
            ->orderBy('fecha_recepcion_sistema', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ComprobanteRecibidoElectronicoResource::collection($comprobantes)
        ]);
    }

    /**
     * Resumen por estado de Hacienda
     */
    #[OA\Get(
        path: "/api/comprobantes-recibidos-electronicos/resumen/por-estado",
        summary: "Resumen por estado de Hacienda",
        description: "Obtiene estadísticas agregadas de comprobantes agrupados por estado de respuesta de Hacienda (Aceptado, Rechazado, Procesando).",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "estado_hacienda", type: "string", example: "Aceptado"),
                                    new OA\Property(property: "total_comprobantes", type: "integer", example: 142),
                                    new OA\Property(property: "monto_total", type: "number", format: "decimal", example: 8450000.00)
                                ],
                                type: "object"
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function resumenPorEstado(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->selectRaw('estado_hacienda, COUNT(*) as total_comprobantes, SUM(total_comprobante) as monto_total')
            ->groupBy('estado_hacienda')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Actualizar respuesta de Hacienda
     */
    #[OA\Put(
        path: "/api/comprobantes-recibidos-electronicos/{id}/actualizar-respuesta-hacienda",
        summary: "Actualizar respuesta de Hacienda",
        description: "Actualiza el comprobante con la respuesta XML recibida de Hacienda (DGT) y su estado final.",
        security: [["sanctum" => []]],
        tags: ["Facturación Electrónica"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del comprobante",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["xml_respuesta_hacienda", "estado_hacienda"],
                properties: [
                    new OA\Property(property: "xml_respuesta_hacienda", type: "string", example: "<?xml version='1.0'..."),
                    new OA\Property(property: "estado_hacienda", type: "string", enum: ["Aceptado", "Rechazado", "Procesando"], example: "Aceptado"),
                    new OA\Property(property: "mensaje_hacienda", type: "string", nullable: true, example: "Comprobante aceptado")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Respuesta de Hacienda actualizada exitosamente"),
            new OA\Response(response: 404, description: "Comprobante no encontrado"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function actualizarRespuestaHacienda(ActualizarRespuestaHaciendaRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $comprobante = ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)->findOrFail($id);

        $comprobante->update([
            'xml_respuesta_hacienda' => $request->xml_respuesta_hacienda,
            'estado_hacienda' => $request->estado_hacienda,
            'mensaje_hacienda' => $request->mensaje_hacienda,
            'fecha_respuesta_hacienda' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta de Hacienda actualizada exitosamente',
            'data' => new ComprobanteRecibidoElectronicoResource($comprobante)
        ]);
    }
}
