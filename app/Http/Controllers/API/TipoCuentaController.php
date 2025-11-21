<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTipoCuentaRequest;
use App\Http\Requests\UpdateTipoCuentaRequest;
use App\Http\Resources\TipoCuentaResource;
use App\Models\TipoCuenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Tipos de Cuentas Contables
 *
 * Gestiona los tipos de cuentas contables (Activo, Pasivo, Patrimonio, Ingresos, Costos, Gastos).
 * Tabla global sin empresa_id, con naturaleza Deudora/Acreedora.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TipoCuentaController extends Controller
{
    /**
     * Listar todos los tipos de cuentas contables
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/tipos-cuenta",
        summary: "Listar tipos de cuenta",
        description: "Obtiene el listado de tipos de cuentas contables (Activo, Pasivo, Patrimonio, Ingresos, Costos, Gastos) con filtros por naturaleza y estado.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        parameters: [
            new OA\Parameter(
                name: "naturaleza",
                in: "query",
                description: "Filtrar por naturaleza contable",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Deudora", "Acreedora"], example: "Deudora")
            ),
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por estado activo. 1 = activos, 0 = inactivos",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "buscar",
                in: "query",
                description: "Buscar por nombre del tipo de cuenta",
                required: false,
                schema: new OA\Schema(type: "string", example: "Activo")
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo por el cual ordenar",
                required: false,
                schema: new OA\Schema(type: "string", default: "nombre")
            ),
            new OA\Parameter(
                name: "sort_order",
                in: "query",
                description: "Orden ascendente o descendente",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Número de registros por página",
                required: false,
                schema: new OA\Schema(type: "integer", default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TipoCuenta")
                        ),
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 15),
                        new OA\Property(property: "total", type: "integer", example: 6)
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TipoCuenta::where('eliminado', 0)->with('cuentasContables');

        // Filtro por naturaleza
        if ($request->filled('naturaleza')) {
            $query->where('naturaleza', $request->naturaleza);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Búsqueda por nombre
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'nombre'), $request->get('sort_order', 'asc'));

        $tipos = $query->paginate($request->get('per_page', 15));

        return TipoCuentaResource::collection($tipos);
    }

    /**
     * Crear un nuevo tipo de cuenta contable
     *
     * @param StoreTipoCuentaRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/tipos-cuenta",
        summary: "Crear tipo de cuenta",
        description: "Crea un nuevo tipo de cuenta contable. Los tipos estándar son: Activo, Pasivo, Patrimonio, Ingresos, Costos, Gastos.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "naturaleza"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Activo"),
                    new OA\Property(property: "descripcion", type: "string", example: "Representa los bienes y derechos de la empresa"),
                    new OA\Property(property: "naturaleza", type: "string", enum: ["Deudora", "Acreedora"], example: "Deudora"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Tipo de cuenta creado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de cuenta creado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoCuenta")
                    ]
                )
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
    public function store(StoreTipoCuentaRequest $request): JsonResponse
    {
        $tipo = TipoCuenta::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta creado exitosamente',
            'data' => new TipoCuentaResource($tipo)
        ], 201);
    }

    /**
     * Mostrar un tipo de cuenta específico
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tipos-cuenta/{id}",
        summary: "Obtener tipo de cuenta",
        description: "Obtiene los detalles de un tipo de cuenta específico, incluyendo las cuentas contables asociadas.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de cuenta",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de cuenta encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoCuenta")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de cuenta no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->with(['cuentasContables' => function ($query) {
                $query->where('eliminado', 0);
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new TipoCuentaResource($tipo)
        ]);
    }

    /**
     * Actualizar un tipo de cuenta existente
     *
     * @param UpdateTipoCuentaRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/tipos-cuenta/{id}",
        summary: "Actualizar tipo de cuenta",
        description: "Actualiza los datos de un tipo de cuenta existente.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de cuenta a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Activo Corriente"),
                    new OA\Property(property: "descripcion", type: "string", example: "Activos con liquidez menor a un año"),
                    new OA\Property(property: "naturaleza", type: "string", enum: ["Deudora", "Acreedora"], example: "Deudora"),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de cuenta actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de cuenta actualizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/TipoCuenta")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de cuenta no encontrado"
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
    public function update(UpdateTipoCuentaRequest $request, int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tipo->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta actualizado exitosamente',
            'data' => new TipoCuentaResource($tipo)
        ]);
    }

    /**
     * Eliminar (soft delete) un tipo de cuenta
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/tipos-cuenta/{id}",
        summary: "Eliminar tipo de cuenta",
        description: "Elimina un tipo de cuenta (soft delete). No se puede eliminar si tiene cuentas contables asignadas.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del tipo de cuenta a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipo de cuenta eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Tipo de cuenta eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Tipo de cuenta no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar - tiene cuentas contables asignadas"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $tipo = TipoCuenta::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no tenga cuentas contables asignadas
        $cuentasCount = $tipo->cuentasContables()->where('eliminado', 0)->count();
        if ($cuentasCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el tipo de cuenta. Tiene {$cuentasCount} cuenta(s) contable(s) asignada(s)"
            ], 422);
        }

        $tipo->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cuenta eliminado exitosamente'
        ]);
    }

    /**
     * Obtener tipos de cuenta por naturaleza (Deudora/Acreedora)
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tipos-cuenta/por-naturaleza",
        summary: "Tipos de cuenta por naturaleza",
        description: "Obtiene los tipos de cuenta filtrados por naturaleza contable (Deudora o Acreedora).",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        parameters: [
            new OA\Parameter(
                name: "naturaleza",
                in: "query",
                description: "Naturaleza contable del tipo de cuenta",
                required: true,
                schema: new OA\Schema(type: "string", enum: ["Deudora", "Acreedora"], example: "Deudora")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipos de cuenta obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TipoCuenta")
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Naturaleza inválida"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function porNaturaleza(Request $request): JsonResponse
    {
        $request->validate([
            'naturaleza' => 'required|in:Deudora,Acreedora'
        ]);

        $tipos = TipoCuenta::where('eliminado', 0)
            ->where('activo', 1)
            ->where('naturaleza', $request->naturaleza)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCuentaResource::collection($tipos)
        ]);
    }

    /**
     * Obtener tipos activos para uso en formularios
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/tipos-cuenta/activos",
        summary: "Tipos de cuenta activos",
        description: "Obtiene únicamente los tipos de cuenta activos, útil para llenar selectores en formularios.",
        security: [["sanctum" => []]],
        tags: ["Catálogos Contables"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Tipos de cuenta activos obtenidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/TipoCuenta")
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function activos(): JsonResponse
    {
        $tipos = TipoCuenta::where('eliminado', 0)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCuentaResource::collection($tipos)
        ]);
    }
}
