<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaContableRequest;
use App\Http\Requests\UpdateCuentaContableRequest;
use App\Http\Resources\CuentaContableResource;
use App\Models\CuentaContable;
use App\Services\CuentaContableService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * CuentaContableController - Versión Refactorizada (FASE 8)
 *
 * Controlador simplificado usando Service Layer Pattern.
 * Delegación: Validación (FormRequest) → Service → Response
 *
 * Reducción de líneas: 581 → ~230 (-60%)
 * Refactorización completada: FASE 8
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaContableController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private CuentaContableService $cuentaContableService) {}

    /**
     * GET /api/cuentas-contables
     * Listar cuentas contables con filtros
     */
    #[OA\Get(
        path: "/api/cuentas-contables",
        summary: "Listar cuentas contables",
        description: "Obtiene el listado de cuentas contables del plan contable (PUC) de la empresa. Soporta filtros por tipo, cuenta padre, código y permisos de movimiento.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(name: "tipo_cuenta_id", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "cuenta_padre_id", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "principales", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "codigo", in: "query", required: false, schema: new OA\Schema(type: "string", example: "1105")),
            new OA\Parameter(name: "permite_movimientos", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 1)),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", default: "codigo")),
            new OA\Parameter(name: "sort_order", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: "Listado de cuentas contables obtenido exitosamente"),
            new OA\Response(response: 500, description: "Error interno del servidor")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CuentaContable::class);

        $cuentas = $this->cuentaContableService->listar(
            empresaId: $this->getEmpresaId(),
            filtros: $request->only([
                'tipo_cuenta_id', 'cuenta_padre_id', 'principales',
                'codigo', 'permite_movimientos', 'sort_by', 'sort_order',
            ]),
            perPage: (int) $request->get('per_page', 15)
        );

        return CuentaContableResource::collection($cuentas);
    }

    /**
     * POST /api/cuentas-contables
     * Crear una nueva cuenta contable
     */
    #[OA\Post(
        path: "/api/cuentas-contables",
        summary: "Crear cuenta contable",
        description: "Crea una nueva cuenta en el plan contable. Puede ser cuenta principal o subcuenta.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "codigo", "tipo_cuenta_id"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Caja General"),
                    new OA\Property(property: "codigo", type: "string", example: "1105"),
                    new OA\Property(property: "descripcion", type: "string"),
                    new OA\Property(property: "tipo_cuenta_id", type: "integer", example: 1),
                    new OA\Property(property: "cuenta_padre_id", type: "integer", example: 1),
                    new OA\Property(property: "permite_movimientos", type: "boolean", example: true),
                    new OA\Property(property: "saldo_actual", type: "number", format: "decimal", example: 0.00),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Cuenta contable creada exitosamente"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function store(StoreCuentaContableRequest $request): CuentaContableResource
    {
        $this->authorize('create', CuentaContable::class);

        $cuenta = $this->cuentaContableService->crear(
            $this->getEmpresaId(),
            $request->validated()
        );

        return (new CuentaContableResource($cuenta))
            ->additional(['success' => true, 'message' => 'Cuenta contable creada exitosamente']);
    }

    /**
     * GET /api/cuentas-contables/{id}
     * Obtener cuenta contable específica
     */
    #[OA\Get(
        path: "/api/cuentas-contables/{id}",
        summary: "Obtener cuenta contable",
        description: "Obtiene los detalles completos de una cuenta contable.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cuenta encontrada exitosamente"),
            new OA\Response(response: 404, description: "Cuenta no encontrada")
        ]
    )]
    public function show(int $id): CuentaContableResource
    {
        $cuenta = $this->cuentaContableService->obtener($this->getEmpresaId(), $id);
        $this->authorize('view', $cuenta);

        return (new CuentaContableResource($cuenta))
            ->additional(['success' => true]);
    }

    /**
     * PUT /api/cuentas-contables/{id}
     * Actualizar cuenta contable
     */
    #[OA\Put(
        path: "/api/cuentas-contables/{id}",
        summary: "Actualizar cuenta contable",
        description: "Actualiza datos de una cuenta contable existente.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string"),
                    new OA\Property(property: "codigo", type: "string"),
                    new OA\Property(property: "descripcion", type: "string"),
                    new OA\Property(property: "tipo_cuenta_id", type: "integer"),
                    new OA\Property(property: "cuenta_padre_id", type: "integer"),
                    new OA\Property(property: "permite_movimientos", type: "boolean"),
                    new OA\Property(property: "saldo_actual", type: "number", format: "decimal"),
                    new OA\Property(property: "activo", type: "boolean")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Cuenta actualizada exitosamente"),
            new OA\Response(response: 404, description: "Cuenta no encontrada"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function update(UpdateCuentaContableRequest $request, int $id): CuentaContableResource
    {
        $empresaId = $this->getEmpresaId();
        $cuenta = $this->cuentaContableService->obtener($empresaId, $id);
        $this->authorize('update', $cuenta);

        $cuenta = $this->cuentaContableService->actualizar($empresaId, $id, $request->validated());

        return (new CuentaContableResource($cuenta))
            ->additional(['success' => true, 'message' => 'Cuenta contable actualizada exitosamente']);
    }

    /**
     * DELETE /api/cuentas-contables/{id}
     * Eliminar cuenta contable (soft delete)
     */
    #[OA\Delete(
        path: "/api/cuentas-contables/{id}",
        summary: "Eliminar cuenta contable",
        description: "Elimina una cuenta contable (soft delete). No se puede eliminar si tiene subcuentas o asientos.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Cuenta eliminada exitosamente"),
            new OA\Response(response: 404, description: "Cuenta no encontrada"),
            new OA\Response(response: 422, description: "No se puede eliminar - tiene subcuentas o asientos")
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();
        $cuenta = $this->cuentaContableService->obtener($empresaId, $id);
        $this->authorize('delete', $cuenta);

        try {
            $this->cuentaContableService->eliminar($empresaId, $id);

            return response()->json([
                'success' => true,
                'message' => 'Cuenta contable eliminada exitosamente',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/cuentas-contables/arbol
     * Obtener árbol jerárquico de cuentas
     */
    #[OA\Get(
        path: "/api/cuentas-contables/arbol",
        summary: "Obtener árbol de cuentas",
        description: "Obtiene la estructura jerárquica completa del plan de cuentas.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        responses: [
            new OA\Response(response: 200, description: "Árbol de cuentas obtenido exitosamente")
        ]
    )]
    public function arbol(): AnonymousResourceCollection
    {
        $cuentas = $this->cuentaContableService->arbol($this->getEmpresaId());

        return CuentaContableResource::collection($cuentas)
            ->additional(['success' => true]);
    }

    /**
     * GET /api/cuentas-contables/para-movimientos
     * Obtener cuentas que permiten movimientos directos
     */
    #[OA\Get(
        path: "/api/cuentas-contables/para-movimientos",
        summary: "Obtener cuentas para movimientos",
        description: "Obtiene cuentas que permiten registrar movimientos directos (para asientos contables).",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        responses: [
            new OA\Response(response: 200, description: "Cuentas para movimientos obtenidas exitosamente")
        ]
    )]
    public function paraMovimientos(): AnonymousResourceCollection
    {
        $cuentas = $this->cuentaContableService->paraMovimientos($this->getEmpresaId());

        return CuentaContableResource::collection($cuentas)
            ->additional(['success' => true]);
    }
}
