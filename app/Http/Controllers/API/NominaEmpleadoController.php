<?php

/**
 * API Controller para `NominaEmpleado`.
 *
 * Provee endpoints para gestionar nóminas por empleado: listados,
 * creación y consultas por periodo. Operaciones sensibles a permisos.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\NominaEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class NominaEmpleadoController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['nomina_empleados', 'nomina', 'rrhh'];
    protected int $cacheTTL = 900; // 15 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/nomina-empleados',
        summary: 'Listar nóminas de empleados',
        description: 'Obtiene un listado paginado de nóminas',
        security: [['sanctum' => []]],
        tags: ['Nómina Empleados'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'periodo_nomina_id',
                description: 'Filtrar por periodo de nómina',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'empleado_id',
                description: 'Filtrar por empleado',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', NominaEmpleado::class);

        $cacheKey = $this->generateCacheKey('nomina_empleados.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = NominaEmpleado::with(['periodoNomina', 'empleado'])
                ->activos();

            if ($request->filled('periodo_nomina_id')) {
                $query->porPeriodo($request->periodo_nomina_id);
            }

            if ($request->filled('empleado_id')) {
                $query->porEmpleado($request->empleado_id);
            }

            $nominas = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json($nominas);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/nomina-empleados',
        summary: 'Crear nómina de empleado',
        description: 'Crea un nuevo registro de nómina para un empleado',
        security: [['sanctum' => []]],
        tags: ['Nómina Empleados'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['periodo_nomina_id', 'empleado_id', 'salario_bruto'],
                properties: [
                    new OA\Property(property: 'periodo_nomina_id', type: 'integer', example: 1),
                    new OA\Property(property: 'empleado_id', type: 'integer', example: 5),
                    new OA\Property(property: 'salario_bruto', type: 'number', format: 'decimal', example: 450000.00),
                    new OA\Property(property: 'horas_extras', type: 'number', format: 'decimal', example: 10.00),
                    new OA\Property(property: 'monto_horas_extras', type: 'number', format: 'decimal', example: 15000.00),
                    new OA\Property(property: 'bonificaciones', type: 'number', format: 'decimal', example: 25000.00),
                    new OA\Property(property: 'deducciones_ccss', type: 'number', format: 'decimal', example: 45000.00),
                    new OA\Property(property: 'deducciones_impuesto_renta', type: 'number', format: 'decimal', example: 20000.00),
                    new OA\Property(property: 'otras_deducciones', type: 'number', format: 'decimal', example: 5000.00),
                    new OA\Property(property: 'observaciones', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Nómina creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', NominaEmpleado::class);

        $validated = $request->validate([
            'periodo_nomina_id' => 'required|exists:periodos_nomina,id',
            'empleado_id' => 'required|exists:empleados,id',
            'salario_bruto' => 'required|numeric|min:0',
            'horas_extras' => 'nullable|numeric|min:0',
            'monto_horas_extras' => 'nullable|numeric|min:0',
            'bonificaciones' => 'nullable|numeric|min:0',
            'deducciones_ccss' => 'nullable|numeric|min:0',
            'deducciones_impuesto_renta' => 'nullable|numeric|min:0',
            'otras_deducciones' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verificar que no exista ya una nómina para este empleado en este periodo
            $exists = NominaEmpleado::where('periodo_nomina_id', $validated['periodo_nomina_id'])
                ->where('empleado_id', $validated['empleado_id'])
                ->where('activo', true)
                ->where('eliminado', false)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ya existe una nómina activa para este empleado en este periodo'
                ], 422);
            }

            // Establecer valores por defecto
            $validated['horas_extras'] = $validated['horas_extras'] ?? 0;
            $validated['monto_horas_extras'] = $validated['monto_horas_extras'] ?? 0;
            $validated['bonificaciones'] = $validated['bonificaciones'] ?? 0;
            $validated['deducciones_ccss'] = $validated['deducciones_ccss'] ?? 0;
            $validated['deducciones_impuesto_renta'] = $validated['deducciones_impuesto_renta'] ?? 0;
            $validated['otras_deducciones'] = $validated['otras_deducciones'] ?? 0;

            $nomina = NominaEmpleado::create($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Nómina creada exitosamente',
                'data' => $nomina->load(['periodoNomina', 'empleado'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear nómina',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/nomina-empleados/{id}',
        summary: 'Obtener nómina específica',
        description: 'Obtiene los detalles de una nómina',
        security: [['sanctum' => []]],
        tags: ['Nómina Empleados'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la nómina',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nómina obtenida exitosamente'
            )
        ]
    )]
    public function show(string $id): \Illuminate\Http\JsonResponse
    {
        $nomina = NominaEmpleado::with(['periodoNomina', 'empleado'])->findOrFail($id);
        $this->authorize('view', $nomina);

        $cacheKey = $this->generateCacheKey("nomina_empleados.show.{$id}");

        return $this->getCached($cacheKey, function () use ($nomina) {
            return response()->json(['data' => $nomina]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/nomina-empleados/{id}',
        summary: 'Actualizar nómina',
        description: 'Actualiza una nómina existente',
        security: [['sanctum' => []]],
        tags: ['Nómina Empleados'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la nómina',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'horas_extras', type: 'number', format: 'decimal'),
                    new OA\Property(property: 'monto_horas_extras', type: 'number', format: 'decimal'),
                    new OA\Property(property: 'bonificaciones', type: 'number', format: 'decimal'),
                    new OA\Property(property: 'observaciones', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nómina actualizada exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $nomina = NominaEmpleado::findOrFail($id);
        $this->authorize('update', $nomina);

        $validated = $request->validate([
            'salario_bruto' => 'sometimes|numeric|min:0',
            'horas_extras' => 'sometimes|numeric|min:0',
            'monto_horas_extras' => 'sometimes|numeric|min:0',
            'bonificaciones' => 'sometimes|numeric|min:0',
            'deducciones_ccss' => 'sometimes|numeric|min:0',
            'deducciones_impuesto_renta' => 'sometimes|numeric|min:0',
            'otras_deducciones' => 'sometimes|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $nomina->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Nómina actualizada exitosamente',
                'data' => $nomina->fresh(['periodoNomina', 'empleado'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar nómina',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/nomina-empleados/{id}',
        summary: 'Eliminar nómina',
        description: 'Elimina (soft delete) una nómina',
        security: [['sanctum' => []]],
        tags: ['Nómina Empleados'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la nómina',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nómina eliminada exitosamente'
            )
        ]
    )]
    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $nomina = NominaEmpleado::findOrFail($id);
        $this->authorize('delete', $nomina);

        DB::beginTransaction();
        try {
            $nomina->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Nómina eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar nómina',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
