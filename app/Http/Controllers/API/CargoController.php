<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCargoRequest;
use App\Http\Requests\UpdateCargoRequest;
use App\Http\Resources\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de cargos de empleados
 * 
 * Define posiciones laborales (Gerente, Vendedor, Contador, etc.)
 * Nota: Tabla global sin empresa_id según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class CargoController extends Controller
{
    /**
     * Listar todos los cargos
     * 
     * GET /api/cargos
     */
    #[OA\Get(
        path: "/api/cargos",
        summary: "Listar todos los cargos",
        description: "Obtiene un listado de todos los cargos de empleados del sistema. Los cargos son globales (sin empresa_id) y definen posiciones laborales como Gerente, Vendedor, Contador, etc.",
        security: [["sanctum" => []]],
        tags: ["Cargos"],
        parameters: [
            new OA\Parameter(
                name: "activo",
                in: "query",
                description: "Filtrar por cargos activos o inactivos",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de cargos obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Cargo")
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
        $query = Cargo::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $cargos = $query->get();

        return CargoResource::collection($cargos);
    }

    /**
     * Crear un nuevo cargo
     * 
     * POST /api/cargos
     */
    #[OA\Post(
        path: "/api/cargos",
        summary: "Crear un nuevo cargo",
        description: "Registra un nuevo cargo de empleado en el sistema. El nombre debe ser único (case-insensitive).",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Gerente de Ventas"),
                    new OA\Property(property: "descripcion", type: "string", example: "Responsable de gestionar el equipo de ventas")
                ]
            )
        ),
        tags: ["Cargos"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Cargo creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Cargo")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación (nombre duplicado)"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function store(StoreCargoRequest $request): JsonResponse
    {
        $cargo = Cargo::create($request->validated());

        return (new CargoResource($cargo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un cargo específico
     * 
     * GET /api/cargos/{id}
     */
    #[OA\Get(
        path: "/api/cargos/{id}",
        summary: "Obtener un cargo específico",
        description: "Obtiene los detalles de un cargo específico, incluyendo la lista de empleados asignados.",
        security: [["sanctum" => []]],
        tags: ["Cargos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del cargo",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cargo encontrado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Cargo")
            ),
            new OA\Response(
                response: 404,
                description: "Cargo no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id): CargoResource
    {
        $cargo = Cargo::with('empleados')->findOrFail($id);

        return new CargoResource($cargo);
    }

    /**
     * Actualizar un cargo existente
     * 
     * PUT/PATCH /api/cargos/{id}
     */
    #[OA\Put(
        path: "/api/cargos/{id}",
        summary: "Actualizar un cargo existente",
        description: "Actualiza los datos de un cargo existente. El nombre debe ser único.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Gerente de Ventas"),
                    new OA\Property(property: "descripcion", type: "string", example: "Responsable de gestionar el equipo de ventas")
                ]
            )
        ),
        tags: ["Cargos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del cargo a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cargo actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Cargo")
            ),
            new OA\Response(
                response: 404,
                description: "Cargo no encontrado"
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
    public function update(UpdateCargoRequest $request, int $id): CargoResource
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->update($request->validated());

        return new CargoResource($cargo);
    }

    /**
     * Eliminar un cargo (soft delete)
     * 
     * DELETE /api/cargos/{id}
     */
    #[OA\Delete(
        path: "/api/cargos/{id}",
        summary: "Eliminar un cargo",
        description: "Realiza un soft delete del cargo especificado. No permite eliminar cargos que tengan empleados asignados.",
        security: [["sanctum" => []]],
        tags: ["Cargos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del cargo a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cargo eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Cargo eliminado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Cargo")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Cargo no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar el cargo porque tiene empleados asignados"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $cargo = Cargo::findOrFail($id);

        // Validar que no tenga empleados asignados
        if ($cargo->empleados()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el cargo porque tiene empleados asignados'
            ], 422);
        }

        $cargo->eliminado = 1;
        $cargo->activo = 0;
        $cargo->save();

        return response()->json([
            'message' => 'Cargo eliminado exitosamente',
            'data' => new CargoResource($cargo)
        ], 200);
    }
}
