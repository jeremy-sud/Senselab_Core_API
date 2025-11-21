<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRutaRequest;
use App\Http\Requests\UpdateRutaRequest;
use App\Http\Resources\RutaResource;
use App\Models\Ruta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Rutas de Transporte
 *
 * Gestiona las rutas de transporte definiendo trayectos (origen-destino).
 * Incluye distancia, duración estimada y tarifa base.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class RutaController extends Controller
{
    /**
     * Listar todas las rutas de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/rutas",
        summary: "Listar rutas",
        description: "Obtiene la lista paginada de rutas de transporte. Permite filtrar por origen, destino y estado activo.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "origen",
                in: "query",
                description: "Filtrar por ciudad de origen",
                required: false,
                schema: new OA\Schema(type: "string", example: "San José")
            ),
            new OA\Parameter(
                name: "destino",
                in: "query",
                description: "Filtrar por ciudad de destino",
                required: false,
                schema: new OA\Schema(type: "string", example: "Limón")
            ),
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo",
                required: false,
                schema: new OA\Schema(type: "integer", enum: [0, 1], example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de rutas obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa']);

        // Filtro por origen o destino
        if ($request->filled('origen')) {
            $query->where('origen', 'like', '%' . $request->origen . '%');
        }

        if ($request->filled('destino')) {
            $query->where('destino', 'like', '%' . $request->destino . '%');
        }

        // Filtro por activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        $rutas = $query->orderBy('nombre')->paginate(15);

        return RutaResource::collection($rutas);
    }

    /**
     * Crear una nueva ruta
     *
     * @param StoreRutaRequest $request
     * @return RutaResource
     */
    #[OA\Post(
        path: "/api/rutas",
        summary: "Crear ruta",
        description: "Registra una nueva ruta de transporte con origen, destino, distancia, duración y tarifa base.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "origen", "destino", "distancia_km", "duracion_estimada", "tarifa_base"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 150, example: "San José - Limón"),
                    new OA\Property(property: "origen", type: "string", maxLength: 100, example: "San José"),
                    new OA\Property(property: "destino", type: "string", maxLength: 100, example: "Limón"),
                    new OA\Property(property: "distancia_km", type: "number", format: "decimal", example: 165.50),
                    new OA\Property(property: "duracion_estimada", type: "integer", example: 180, description: "Duración en minutos"),
                    new OA\Property(property: "tarifa_base", type: "number", format: "decimal", example: 5500.00),
                    new OA\Property(property: "observaciones", type: "string", nullable: true, example: "Ruta turística principal"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1, description: "Opcional, por defecto 1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ruta creada exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreRutaRequest $request): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::create([
            'empresa_id' => $empresaId,
            'nombre' => $request->nombre,
            'origen' => $request->origen,
            'destino' => $request->destino,
            'distancia_km' => $request->distancia_km,
            'duracion_estimada' => $request->duracion_estimada,
            'tarifa_base' => $request->tarifa_base,
            'observaciones' => $request->observaciones,
            'activo' => $request->activo ?? 1
        ]);

        return new RutaResource($ruta->load(['empresa']));
    }

    /**
     * Mostrar una ruta específica
     *
     * @param int $id
     * @param Request $request
     * @return RutaResource
     */
    #[OA\Get(
        path: "/api/rutas/{id}",
        summary: "Obtener ruta",
        description: "Obtiene el detalle de una ruta con sus horarios asociados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Ruta obtenida exitosamente"),
            new OA\Response(response: 404, description: "Ruta no encontrada"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id, Request $request): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'horariosRuta'])
            ->findOrFail($id);

        return new RutaResource($ruta);
    }

    /**
     * Actualizar una ruta
     *
     * @param UpdateRutaRequest $request
     * @param int $id
     * @return RutaResource
     */
    #[OA\Put(
        path: "/api/rutas/{id}",
        summary: "Actualizar ruta",
        description: "Actualiza una ruta de transporte existente.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", maxLength: 150, example: "San José - Limón"),
                    new OA\Property(property: "origen", type: "string", maxLength: 100, example: "San José"),
                    new OA\Property(property: "destino", type: "string", maxLength: 100, example: "Limón"),
                    new OA\Property(property: "distancia_km", type: "number", format: "decimal", example: 165.50),
                    new OA\Property(property: "duracion_estimada", type: "integer", example: 180),
                    new OA\Property(property: "tarifa_base", type: "number", format: "decimal", example: 5500.00),
                    new OA\Property(property: "observaciones", type: "string", nullable: true, example: "Ruta turística principal"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Ruta actualizada exitosamente"),
            new OA\Response(response: 404, description: "Ruta no encontrada"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateRutaRequest $request, int $id): RutaResource
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $ruta->update($request->only([
            'nombre',
            'origen',
            'destino',
            'distancia_km',
            'duracion_estimada',
            'tarifa_base',
            'observaciones',
            'activo'
        ]));

        return new RutaResource($ruta->load(['empresa']));
    }

    /**
     * Eliminar (soft delete) una ruta
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/rutas/{id}",
        summary: "Eliminar ruta",
        description: "Elimina lógicamente una ruta. No permite eliminar rutas con horarios activos (no finalizados).",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Ruta eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Ruta eliminada exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Ruta no encontrada"),
            new OA\Response(response: 422, description: "No se puede eliminar una ruta con horarios activos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        // Validar que no tenga horarios activos
        if ($ruta->horariosRuta()->where('estado', '!=', 'Finalizado')->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar una ruta con horarios activos'
            ], 422);
        }

        $ruta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'message' => 'Ruta eliminada exitosamente'
        ]);
    }

    /**
     * Listar rutas activas para selección
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/rutas/activas",
        summary: "Listar rutas activas",
        description: "Obtiene lista simplificada de rutas activas para uso en selectores.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de rutas activas obtenida exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nombre", type: "string", example: "San José - Limón"),
                            new OA\Property(property: "origen", type: "string", example: "San José"),
                            new OA\Property(property: "destino", type: "string", example: "Limón"),
                            new OA\Property(property: "tarifa_base", type: "number", format: "decimal", example: 5500.00),
                            new OA\Property(property: "duracion_estimada", type: "integer", example: 180)
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function activas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $rutas = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre', 'origen', 'destino', 'tarifa_base', 'duracion_estimada')
            ->orderBy('nombre')
            ->get();

        return response()->json($rutas);
    }

    /**
     * Calcular tarifa estimada con parámetros adicionales
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/rutas/{id}/calcular-tarifa",
        summary: "Calcular tarifa",
        description: "Calcula la tarifa total para una ruta considerando cantidad de pasajeros y descuentos opcionales.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cantidad_pasajeros", type: "integer", example: 2, description: "Opcional, por defecto 1"),
                    new OA\Property(property: "descuento_porcentaje", type: "number", format: "decimal", example: 10.00, description: "Opcional, por defecto 0")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tarifa calculada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "ruta",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "nombre", type: "string", example: "San José - Limón"),
                                new OA\Property(property: "origen", type: "string", example: "San José"),
                                new OA\Property(property: "destino", type: "string", example: "Limón")
                            ],
                            type: "object"
                        ),
                        new OA\Property(
                            property: "calculo",
                            properties: [
                                new OA\Property(property: "tarifa_base_unitaria", type: "string", example: "5500.00"),
                                new OA\Property(property: "cantidad_pasajeros", type: "integer", example: 2),
                                new OA\Property(property: "subtotal", type: "string", example: "11000.00"),
                                new OA\Property(property: "descuento_porcentaje", type: "number", example: 10),
                                new OA\Property(property: "monto_descuento", type: "string", example: "1100.00"),
                                new OA\Property(property: "tarifa_final", type: "string", example: "9900.00")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Ruta no encontrada"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function calcularTarifa(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        $cantidadPasajeros = $request->input('cantidad_pasajeros', 1);
        $descuento = $request->input('descuento_porcentaje', 0);

        $tarifaBase = $ruta->tarifa_base * $cantidadPasajeros;
        $montoDescuento = ($tarifaBase * $descuento) / 100;
        $tarifaFinal = $tarifaBase - $montoDescuento;

        return response()->json([
            'ruta' => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'origen' => $ruta->origen,
                'destino' => $ruta->destino
            ],
            'calculo' => [
                'tarifa_base_unitaria' => number_format($ruta->tarifa_base, 2),
                'cantidad_pasajeros' => $cantidadPasajeros,
                'subtotal' => number_format($tarifaBase, 2),
                'descuento_porcentaje' => $descuento,
                'monto_descuento' => number_format($montoDescuento, 2),
                'tarifa_final' => number_format($tarifaFinal, 2)
            ]
        ]);
    }

    /**
     * Obtener estadísticas de una ruta
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/rutas/{id}/estadisticas",
        summary: "Estadísticas de ruta",
        description: "Obtiene estadísticas de viajes de una ruta: total, finalizados, en curso y programados.",
        security: [["sanctum" => []]],
        tags: ["Transporte"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la ruta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estadísticas obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "ruta",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "nombre", type: "string", example: "San José - Limón"),
                                new OA\Property(property: "origen", type: "string", example: "San José"),
                                new OA\Property(property: "destino", type: "string", example: "Limón"),
                                new OA\Property(property: "distancia_km", type: "number", format: "decimal", example: 165.50)
                            ],
                            type: "object"
                        ),
                        new OA\Property(
                            property: "estadisticas",
                            properties: [
                                new OA\Property(property: "total_viajes", type: "integer", example: 156),
                                new OA\Property(property: "finalizados", type: "integer", example: 142),
                                new OA\Property(property: "en_curso", type: "integer", example: 2),
                                new OA\Property(property: "programados", type: "integer", example: 12)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Ruta no encontrada"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function estadisticas(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $ruta = Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['horariosRuta'])
            ->findOrFail($id);

        $totalViajes = $ruta->horariosRuta()->count();
        $viajesFinalizados = $ruta->horariosRuta()->where('estado', 'Finalizado')->count();
        $viajesEnCurso = $ruta->horariosRuta()->where('estado', 'En Viaje')->count();
        $viajesProgramados = $ruta->horariosRuta()->where('estado', 'Programado')->count();

        return response()->json([
            'ruta' => [
                'id' => $ruta->id,
                'nombre' => $ruta->nombre,
                'origen' => $ruta->origen,
                'destino' => $ruta->destino,
                'distancia_km' => $ruta->distancia_km
            ],
            'estadisticas' => [
                'total_viajes' => $totalViajes,
                'finalizados' => $viajesFinalizados,
                'en_curso' => $viajesEnCurso,
                'programados' => $viajesProgramados
            ]
        ]);
    }
}
