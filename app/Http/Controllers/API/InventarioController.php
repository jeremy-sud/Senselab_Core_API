<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Http\Requests\StoreSalidaInventarioRequest;
use App\Http\Resources\EntradaInventarioResource;
use App\Http\Resources\SalidaInventarioResource;
use App\Models\EntradaInventario;
use App\Models\SalidaInventario;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de movimientos de inventario
 * 
 * Maneja tanto entradas como salidas de inventario con:
 * - Filtrado por almacén, tipo de movimiento y fechas
 * - Control de estados (Pendiente, Procesada, Cancelada)
 * - Trazabilidad completa
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class InventarioController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['inventario', 'movimientos-inventario'];
    protected int $cacheTTL = 1200; // 20 minutos - movimientos semi-dinámicos

    /**
     * Listar todas las entradas de inventario
     * 
     * GET /api/inventario/entradas
     */
    #[OA\Get(
        path: "/api/inventario/entradas",
        summary: "Listar entradas de inventario",
        description: "Obtiene un listado de todas las entradas de inventario de la empresa. Permite filtrar por almacén, estado, tipo de entrada y rango de fechas.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "almacen_id",
                in: "query",
                description: "Filtrar por almacén",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Pendiente", "Procesada", "Cancelada"], example: "Pendiente")
            ),
            new OA\Parameter(
                name: "tipo_entrada",
                in: "query",
                description: "Filtrar por tipo de entrada",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Compra", "Traslado", "Ajuste", "Devolución", "Producción", "Otro"], example: "Compra")
            ),
            new OA\Parameter(
                name: "fecha_desde",
                in: "query",
                description: "Fecha de inicio del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "fecha_hasta",
                in: "query",
                description: "Fecha fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de entradas obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/EntradaInventario")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function indexEntradas(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EntradaInventario::class);
        
        $empresaId = auth()->user()->empresa_id;
        
        $cacheKey = $this->getCacheKey('entradas', [
            'almacen_id' => $request->input('almacen_id'),
            'estado' => $request->input('estado'),
            'tipo_entrada' => $request->input('tipo_entrada'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta')
        ]);
        
        $entradas = $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = EntradaInventario::where('empresa_id', $empresaId)
                ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles']);

            // Filtros opcionales
            if ($request->filled('almacen_id')) {
                $query->where('almacen_id', $request->almacen_id);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo_entrada')) {
                $query->where('tipo_entrada', $request->tipo_entrada);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_entrada', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_entrada', '<=', $request->fecha_hasta);
            }

            return $query->orderBy('fecha_entrada', 'desc')->get();
        });

        return EntradaInventarioResource::collection($entradas);
    }

    /**
     * Crear una nueva entrada de inventario
     * 
     * POST /api/inventario/entradas
     */
    #[OA\Post(
        path: "/api/inventario/entradas",
        summary: "Crear entrada de inventario",
        description: "Registra una nueva entrada de inventario al sistema.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["almacen_id", "fecha_entrada", "tipo_entrada", "estado"],
                properties: [
                    new OA\Property(property: "almacen_id", type: "integer", example: 2),
                    new OA\Property(property: "fecha_entrada", type: "string", format: "date-time", example: "2024-01-15T10:30:00Z"),
                    new OA\Property(property: "tipo_entrada", type: "string", enum: ["Compra", "Traslado", "Ajuste", "Devolución", "Producción", "Otro"], example: "Compra"),
                    new OA\Property(property: "orden_compra_id", type: "integer", example: 15),
                    new OA\Property(property: "proveedor_id", type: "integer", example: 5),
                    new OA\Property(property: "documento_referencia", type: "string", example: "FACT-2024-001"),
                    new OA\Property(property: "estado", type: "string", enum: ["Pendiente", "Procesada", "Cancelada"], example: "Pendiente"),
                    new OA\Property(property: "monto_total", type: "number", format: "decimal", example: 125000.00),
                    new OA\Property(property: "observaciones", type: "string", example: "Recibido en buen estado"),
                    new OA\Property(property: "descripcion", type: "string", example: "Entrada de mercadería según orden #15")
                ]
            )
        ),
        tags: ["Inventario"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Entrada creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/EntradaInventario")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function storeEntrada(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', EntradaInventario::class);
        
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $entrada = EntradaInventario::create($validated);
        $entrada->load(['almacen', 'ordenCompra', 'proveedor', 'detalles']);

        return (new EntradaInventarioResource($entrada))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una entrada específica
     * 
     * GET /api/inventario/entradas/{id}
     */
    #[OA\Get(
        path: "/api/inventario/entradas/{id}",
        summary: "Obtener entrada de inventario",
        description: "Obtiene los detalles de una entrada de inventario específica, incluyendo sus detalles de productos.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la entrada",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Entrada encontrada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/EntradaInventario")
            ),
            new OA\Response(
                response: 404,
                description: "Entrada no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function showEntrada(int $id): EntradaInventarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);
        
        $this->authorize('view', $entrada);

        return new EntradaInventarioResource($entrada);
    }

    /**
     * Listar todas las salidas de inventario
     * 
     * GET /api/inventario/salidas
     */
    #[OA\Get(
        path: "/api/inventario/salidas",
        summary: "Listar salidas de inventario",
        description: "Obtiene un listado de todas las salidas de inventario de la empresa. Permite filtrar por almacén, estado, tipo de salida y rango de fechas.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "almacen_id",
                in: "query",
                description: "Filtrar por almacén",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Pendiente", "Procesada", "Cancelada"], example: "Procesada")
            ),
            new OA\Parameter(
                name: "tipo_salida",
                in: "query",
                description: "Filtrar por tipo de salida",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Venta", "Traslado", "Ajuste", "Devolución", "Producción", "Merma", "Otro"], example: "Venta")
            ),
            new OA\Parameter(
                name: "fecha_desde",
                in: "query",
                description: "Fecha de inicio del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "fecha_hasta",
                in: "query",
                description: "Fecha fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de salidas obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SalidaInventario")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function indexSalidas(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalidaInventario::class);
        
        $empresaId = auth()->user()->empresa_id;
        
        $cacheKey = $this->getCacheKey('salidas', [
            'almacen_id' => $request->input('almacen_id'),
            'estado' => $request->input('estado'),
            'tipo_salida' => $request->input('tipo_salida'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta')
        ]);
        
        $salidas = $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = SalidaInventario::where('empresa_id', $empresaId)
                ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);

            // Filtros opcionales
            if ($request->filled('almacen_id')) {
                $query->where('almacen_id', $request->almacen_id);
            }

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('tipo_salida')) {
                $query->where('tipo_salida', $request->tipo_salida);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
            }

            return $query->orderBy('fecha_salida', 'desc')->get();
        });

        return SalidaInventarioResource::collection($salidas);
    }

    /**
     * Crear una nueva salida de inventario
     * 
     * POST /api/inventario/salidas
     */
    #[OA\Post(
        path: "/api/inventario/salidas",
        summary: "Crear salida de inventario",
        description: "Registra una nueva salida de inventario del sistema.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["almacen_id", "fecha_salida", "tipo_salida", "estado"],
                properties: [
                    new OA\Property(property: "almacen_id", type: "integer", example: 2),
                    new OA\Property(property: "fecha_salida", type: "string", format: "date-time", example: "2024-01-15T10:30:00Z"),
                    new OA\Property(property: "tipo_salida", type: "string", enum: ["Venta", "Traslado", "Ajuste", "Devolución", "Producción", "Merma", "Otro"], example: "Venta"),
                    new OA\Property(property: "venta_id", type: "integer", example: 20),
                    new OA\Property(property: "cliente_id", type: "integer", example: 12),
                    new OA\Property(property: "proveedor_id", type: "integer", example: 5),
                    new OA\Property(property: "documento_referencia", type: "string", example: "FACT-2024-050"),
                    new OA\Property(property: "estado", type: "string", enum: ["Pendiente", "Procesada", "Cancelada"], example: "Procesada"),
                    new OA\Property(property: "monto_total", type: "number", format: "decimal", example: 85000.00),
                    new OA\Property(property: "observaciones", type: "string", example: "Entregado completo"),
                    new OA\Property(property: "descripcion", type: "string", example: "Salida para venta #20")
                ]
            )
        ),
        tags: ["Inventario"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Salida creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/SalidaInventario")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function storeSalida(StoreSalidaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', SalidaInventario::class);
        
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $salida = SalidaInventario::create($validated);
        $salida->load(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);

        return (new SalidaInventarioResource($salida))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una salida específica
     * 
     * GET /api/inventario/salidas/{id}
     */
    #[OA\Get(
        path: "/api/inventario/salidas/{id}",
        summary: "Obtener salida de inventario",
        description: "Obtiene los detalles de una salida de inventario específica, incluyendo sus detalles de productos.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la salida",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Salida encontrada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/SalidaInventario")
            ),
            new OA\Response(
                response: 404,
                description: "Salida no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function showSalida(int $id): SalidaInventarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);
        
        $this->authorize('view', $salida);

        return new SalidaInventarioResource($salida);
    }

    /**
     * Cancelar una entrada de inventario
     * 
     * POST /api/inventario/entradas/{id}/cancelar
     */
    #[OA\Post(
        path: "/api/inventario/entradas/{id}/cancelar",
        summary: "Cancelar entrada de inventario",
        description: "Cancela una entrada de inventario. No permite cancelar entradas ya procesadas.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la entrada a cancelar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Entrada cancelada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Entrada de inventario cancelada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/EntradaInventario")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Entrada no encontrada"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede cancelar una entrada ya procesada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function cancelarEntrada(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'message' => 'No se puede cancelar una entrada ya procesada'
            ], 422);
        }

        $entrada->estado = 'Cancelada';
        $entrada->save();

        return response()->json([
            'message' => 'Entrada de inventario cancelada exitosamente',
            'data' => new EntradaInventarioResource($entrada)
        ], 200);
    }

    /**
     * Cancelar una salida de inventario
     * 
     * POST /api/inventario/salidas/{id}/cancelar
     */
    #[OA\Post(
        path: "/api/inventario/salidas/{id}/cancelar",
        summary: "Cancelar salida de inventario",
        description: "Cancela una salida de inventario. No permite cancelar salidas ya procesadas.",
        security: [["sanctum" => []]],
        tags: ["Inventario"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la salida a cancelar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Salida cancelada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Salida de inventario cancelada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/SalidaInventario")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Salida no encontrada"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede cancelar una salida ya procesada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function cancelarSalida(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'message' => 'No se puede cancelar una salida ya procesada'
            ], 422);
        }

        $salida->estado = 'Cancelada';
        $salida->save();

        return response()->json([
            'message' => 'Salida de inventario cancelada exitosamente',
            'data' => new SalidaInventarioResource($salida)
        ], 200);
    }
}
