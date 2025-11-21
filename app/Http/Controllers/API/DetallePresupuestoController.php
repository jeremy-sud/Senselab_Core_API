<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDetallePresupuestoRequest;
use App\Http\Requests\UpdateDetallePresupuestoRequest;
use App\Http\Resources\DetallePresupuestoResource;
use App\Models\DetallePresupuesto;
use App\Models\Presupuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Controlador para Detalle de Presupuestos
 * 
 * Gestiona las cuentas contables específicas de cada presupuesto con sus montos.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetallePresupuestoController extends Controller
{
    /**
     * Listar detalles de un presupuesto
     */
    #[OA\Get(
        path: "/api/presupuestos/{presupuestoId}/detalles",
        summary: "Listar cuentas del presupuesto",
        description: "Obtiene todas las cuentas contables asignadas a un presupuesto con sus montos presupuestados.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "presupuestoId",
                in: "path",
                description: "ID del presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de detalles obtenida exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request, int $presupuestoId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)->findOrFail($presupuestoId);

        $detalles = DetallePresupuesto::where('presupuesto_id', $presupuestoId)
            ->with('cuentaContable')
            ->get();

        return response()->json([
            'success' => true,
            'data' => DetallePresupuestoResource::collection($detalles)
        ]);
    }

    /**
     * Agregar cuenta al presupuesto
     */
    #[OA\Post(
        path: "/api/detalles-presupuestos",
        summary: "Agregar cuenta al presupuesto",
        description: "Agrega una cuenta contable con su monto presupuestado. No permite agregar cuentas a presupuestos finalizados.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["presupuesto_id", "cuenta_contable_id", "monto_presupuestado"],
                properties: [
                    new OA\Property(property: "presupuesto_id", type: "integer", example: 1),
                    new OA\Property(property: "cuenta_contable_id", type: "integer", example: 5),
                    new OA\Property(property: "monto_presupuestado", type: "number", format: "decimal", example: 250000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Cuenta agregada exitosamente"),
            new OA\Response(response: 422, description: "No se pueden agregar cuentas a un presupuesto finalizado"),
            new OA\Response(response: 404, description: "Presupuesto no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StoreDetallePresupuestoRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $presupuesto = Presupuesto::where('empresa_id', $empresaId)
            ->findOrFail($request->presupuesto_id);

        if ($presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden agregar cuentas a un presupuesto finalizado'
            ], 422);
        }

        $detalle = DetallePresupuesto::create([
            'presupuesto_id' => $request->presupuesto_id,
            'cuenta_contable_id' => $request->cuenta_contable_id,
            'monto_presupuestado' => $request->monto_presupuestado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta agregada al presupuesto exitosamente',
            'data' => new DetallePresupuestoResource($detalle->load('cuentaContable'))
        ], 201);
    }

    /**
     * Mostrar detalle específico
     */
    #[OA\Get(
        path: "/api/detalles-presupuestos/{id}",
        summary: "Obtener detalle de presupuesto",
        description: "Obtiene el detalle de una cuenta específica dentro de un presupuesto.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del detalle de presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Detalle obtenido exitosamente"),
            new OA\Response(response: 404, description: "Detalle no encontrado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $detalle = DetallePresupuesto::with(['presupuesto', 'cuentaContable'])
            ->findOrFail($id);

        $empresaId = $request->user()->empresa_id;
        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new DetallePresupuestoResource($detalle)
        ]);
    }

    /**
     * Actualizar detalle de presupuesto
     */
    #[OA\Put(
        path: "/api/detalles-presupuestos/{id}",
        summary: "Actualizar detalle de presupuesto",
        description: "Actualiza el monto presupuestado de una cuenta. No permite modificar presupuestos finalizados.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del detalle de presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["monto_presupuestado"],
                properties: [
                    new OA\Property(property: "monto_presupuestado", type: "number", format: "decimal", example: 300000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Detalle actualizado exitosamente"),
            new OA\Response(response: 404, description: "Detalle no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un presupuesto finalizado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdateDetallePresupuestoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetallePresupuesto::with('presupuesto')
            ->findOrFail($id);

        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un presupuesto finalizado'
            ], 422);
        }

        $detalle->update([
            'monto_presupuestado' => $request->monto_presupuestado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detalle actualizado exitosamente',
            'data' => new DetallePresupuestoResource($detalle->fresh('cuentaContable'))
        ]);
    }

    /**
     * Eliminar cuenta del presupuesto
     */
    #[OA\Delete(
        path: "/api/detalles-presupuestos/{id}",
        summary: "Eliminar cuenta del presupuesto",
        description: "Elimina una cuenta contable del presupuesto. No permite eliminar cuentas de presupuestos finalizados.",
        security: [["sanctum" => []]],
        tags: ["Presupuestos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del detalle de presupuesto",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cuenta eliminada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Cuenta eliminada del presupuesto exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Detalle no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar una cuenta de un presupuesto finalizado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetallePresupuesto::with('presupuesto')
            ->findOrFail($id);

        if ($detalle->presupuesto->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->presupuesto->estado === 'Finalizado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta de un presupuesto finalizado'
            ], 422);
        }

        $detalle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta eliminada del presupuesto exitosamente'
        ]);
    }
}
