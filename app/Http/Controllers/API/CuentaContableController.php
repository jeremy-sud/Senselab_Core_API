<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaContableRequest;
use App\Http\Requests\UpdateCuentaContableRequest;
use App\Http\Resources\CuentaContableResource;
use App\Models\CuentaContable;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Cuentas Contables
 *
 * Gestiona el plan de cuentas contables (PUC) de la empresa.
 * Estructura jerárquica para registrar asientos contables.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaContableController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /** @var array<string> */
    protected array $cacheTags = ['cuentas-contables', 'contabilidad'];
    protected int $cacheTTL = 3600; // 1 hora - plan contable cambia ocasionalmente
    /**
     * Listar todas las cuentas contables de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/cuentas-contables",
        summary: "Listar cuentas contables",
        description: "Obtiene el listado de cuentas contables del plan contable (PUC) de la empresa. Soporta filtros por tipo, cuenta padre, código y permisos de movimiento.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "tipo_cuenta_id",
                in: "query",
                description: "Filtrar por tipo de cuenta",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "cuenta_padre_id",
                in: "query",
                description: "Filtrar por cuenta padre (subcuentas de una cuenta específica)",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "principales",
                in: "query",
                description: "Filtrar solo cuentas principales (sin cuenta padre). 1 = solo principales",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "codigo",
                in: "query",
                description: "Buscar por código de cuenta (búsqueda parcial)",
                required: false,
                schema: new OA\Schema(type: "string", example: "1105")
            ),
            new OA\Parameter(
                name: "permite_movimientos",
                in: "query",
                description: "Filtrar cuentas que permiten movimientos directos. 1 = permite, 0 = no permite",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "sort_by",
                in: "query",
                description: "Campo por el cual ordenar",
                required: false,
                schema: new OA\Schema(type: "string", default: "codigo", example: "nombre")
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
                description: "Listado de cuentas contables obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/CuentaContable")
                        ),
                        new OA\Property(property: "current_page", type: "integer", example: 1),
                        new OA\Property(property: "per_page", type: "integer", example: 15),
                        new OA\Property(property: "total", type: "integer", example: 100)
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
        $this->authorize('viewAny', CuentaContable::class);
        $empresaId = $this->getEmpresaId();
        
        $cacheKey = $this->getCacheKey('index', [
            'empresa_id' => $empresaId,
            'tipo_cuenta_id' => $request->get('tipo_cuenta_id'),
            'cuenta_padre_id' => $request->get('cuenta_padre_id'),
            'principales' => $request->get('principales'),
            'codigo' => $request->get('codigo'),
            'permite_movimientos' => $request->get('permite_movimientos'),
            'sort_by' => $request->get('sort_by', 'codigo'),
            'sort_order' => $request->get('sort_order', 'asc'),
            'per_page' => $request->get('per_page', 15)
        ]);

        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId) {
            $query = CuentaContable::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

            // Filtro por tipo de cuenta
            if ($request->filled('tipo_cuenta_id')) {
                $query->where('tipo_cuenta_id', $request->tipo_cuenta_id);
            }

            // Filtro por cuenta padre
            if ($request->filled('cuenta_padre_id')) {
                $query->where('cuenta_padre_id', $request->cuenta_padre_id);
            }

            // Filtro solo cuentas principales (sin padre)
            if ($request->filled('principales') && $request->principales == 1) {
                $query->whereNull('cuenta_padre_id');
            }

            // Filtro por código
            if ($request->filled('codigo')) {
                $query->where('codigo', 'like', "%{$request->codigo}%");
            }

            // Filtro que permiten movimientos
            if ($request->filled('permite_movimientos')) {
                $query->where('permite_movimientos', $request->permite_movimientos);
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'codigo'), $request->get('sort_order', 'asc'));

            $cuentas = $query->paginate($request->get('per_page', 15));

            return CuentaContableResource::collection($cuentas);
        });
    }

    /**
     * Crear una nueva cuenta contable
     *
     * @param StoreCuentaContableRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/cuentas-contables",
        summary: "Crear cuenta contable",
        description: "Crea una nueva cuenta en el plan contable. Puede ser cuenta principal o subcuenta. El código debe ser único por empresa.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre", "codigo", "tipo_cuenta_id"],
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Caja General"),
                    new OA\Property(property: "codigo", type: "string", example: "1105"),
                    new OA\Property(property: "descripcion", type: "string", example: "Cuenta para control de efectivo en caja"),
                    new OA\Property(property: "tipo_cuenta_id", type: "integer", example: 1),
                    new OA\Property(property: "cuenta_padre_id", type: "integer", example: 1, description: "ID de la cuenta padre (null para cuentas principales)"),
                    new OA\Property(property: "permite_movimientos", type: "boolean", example: true, description: "Indica si la cuenta permite registrar movimientos directos"),
                    new OA\Property(property: "saldo_actual", type: "number", format: "decimal", example: 0.00),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Cuenta contable creada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Cuenta contable creada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/CuentaContable")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación - Código duplicado o cuenta padre no válida"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function store(StoreCuentaContableRequest $request): JsonResponse
    {
        $this->authorize('create', CuentaContable::class);
        $validated = $request->validated();
        $validated['empresa_id'] = $this->getEmpresaId();

        $cuenta = CuentaContable::create($validated);
        $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable creada exitosamente',
            'data' => new CuentaContableResource($cuenta)
        ], 201);
    }

    /**
     * Mostrar una cuenta contable específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/cuentas-contables/{id}",
        summary: "Obtener cuenta contable",
        description: "Obtiene los detalles completos de una cuenta contable, incluyendo su cuenta padre, tipo, subcuentas y asientos relacionados.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la cuenta contable",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cuenta encontrada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/CuentaContable")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Cuenta no encontrada"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas', 'asientos'])
            ->firstOrFail();

        $this->authorize('view', $cuenta);

        return response()->json([
            'success' => true,
            'data' => new CuentaContableResource($cuenta)
        ]);
    }

    /**
     * Actualizar una cuenta contable existente
     *
     * @param UpdateCuentaContableRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/cuentas-contables/{id}",
        summary: "Actualizar cuenta contable",
        description: "Actualiza los datos de una cuenta contable existente. Se aplican validaciones de jerarquía y permisos.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la cuenta a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre", type: "string", example: "Caja General Actualizada"),
                    new OA\Property(property: "codigo", type: "string", example: "1105-01"),
                    new OA\Property(property: "descripcion", type: "string", example: "Descripción actualizada"),
                    new OA\Property(property: "tipo_cuenta_id", type: "integer", example: 1),
                    new OA\Property(property: "cuenta_padre_id", type: "integer", example: 1),
                    new OA\Property(property: "permite_movimientos", type: "boolean", example: true),
                    new OA\Property(property: "saldo_actual", type: "number", format: "decimal", example: 15000.00),
                    new OA\Property(property: "activo", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Cuenta actualizada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Cuenta contable actualizada exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/CuentaContable")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Cuenta no encontrada"
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
    public function update(UpdateCuentaContableRequest $request, int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $this->authorize('update', $cuenta);

        $cuenta->update($request->validated());
        $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable actualizada exitosamente',
            'data' => new CuentaContableResource($cuenta)
        ]);
    }

    /**
     * Eliminar (soft delete) una cuenta contable
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/cuentas-contables/{id}",
        summary: "Eliminar cuenta contable",
        description: "Elimina una cuenta contable (soft delete). No se puede eliminar si tiene subcuentas o asientos contables asociados.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID de la cuenta a eliminar",
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
                        new OA\Property(property: "message", type: "string", example: "Cuenta contable eliminada exitosamente")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Cuenta no encontrada"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar - tiene subcuentas o asientos asociados"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $this->authorize('delete', $cuenta);

        // Validar que no tenga subcuentas
        if ($cuenta->subcuentas()->where('eliminado', 0)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta contable que tiene subcuentas asociadas'
            ], 422);
        }

        // Validar que no tenga asientos contables
        if ($cuenta->asientos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta contable que tiene asientos contables registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);

        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta contable eliminada exitosamente'
        ]);
    }

    /**
     * Obtener el árbol jerárquico de cuentas contables
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/cuentas-contables/arbol",
        summary: "Obtener árbol de cuentas",
        description: "Obtiene la estructura jerárquica completa del plan de cuentas, mostrando cuentas principales y sus subcuentas anidadas.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Árbol de cuentas obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/CuentaContable"),
                            description: "Array de cuentas principales con subcuentas anidadas"
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
    public function arbol(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        // Obtener solo las cuentas principales (sin padre)
        $cuentasPrincipales = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->whereNull('cuenta_padre_id')
            ->with(['subcuentas' => function ($query) {
                $query->where('eliminado', 0)->with('subcuentas');
            }])
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CuentaContableResource::collection($cuentasPrincipales)
        ]);
    }

    /**
     * Obtener cuentas que permiten movimientos (para asientos)
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/cuentas-contables/para-movimientos",
        summary: "Obtener cuentas para movimientos",
        description: "Obtiene únicamente las cuentas contables que permiten registrar movimientos directos (permite_movimientos = true). Estas son las cuentas de detalle que se utilizan en los asientos contables.",
        security: [["sanctum" => []]],
        tags: ["Contabilidad"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Cuentas para movimientos obtenidas exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/CuentaContable"),
                            description: "Array de cuentas que permiten movimientos"
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
    public function paraMovimientos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuentas = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('permite_movimientos', 1)
            ->orderBy('codigo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CuentaContableResource::collection($cuentas)
        ]);
    }
}
