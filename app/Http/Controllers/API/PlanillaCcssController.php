<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PlanillaCcss;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Planillas CCSS',
    description: 'Gestión de planillas de Caja Costarricense de Seguro Social'
)]
class PlanillaCcssController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['planillas-ccss', 'nomina', 'ccss'];
    protected int $cacheTTL = 3600; // 1 hora - planillas estables una vez generadas

    /**
     * Display a listing of the resource.
     */
        #[OA\Get(
        path: '/api/planilla-ccss',
        summary: 'Listar planillas CCSS',
        security: [['sanctum' => []]],
        tags: ['Planillas CCSS'],
        responses: [
            new OA\Response(response: 200, description: 'Listado de planillas CCSS'),
        ]
    )]

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', PlanillaCcss::class);

        $cacheKey = $this->getCacheKey('index', [
            'estado' => $request->get('estado'),
            'periodo' => $request->get('periodo'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'per_page' => $request->get('per_page', 20),
        ]);

        $planillas = $this->cacheQueryIfEnabled($cacheKey, function () use ($request) {
            $query = PlanillaCcss::query()
                ->where('empresa_id', auth('sanctum')->user()->empresa_id)
                ->with(['periodoNomina']);

            if ($request->filled('estado')) {
                $query->where('estado', $request->get('estado'));
            }

            if ($request->filled('periodo')) {
                $query->porPeriodo($request->get('periodo'));
            }

            if ($request->filled('fecha_desde')) {
                $query->where('fecha_generacion', '>=', $request->get('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->where('fecha_generacion', '<=', $request->get('fecha_hasta'));
            }

            return $query->orderBy('periodo', 'desc')
                ->paginate($request->get('per_page', 20));
        });

        return response()->json($planillas);
    }

    /**
     * Store a newly created resource in storage.
     */
        #[OA\Post(
        path: '/api/planilla-ccss',
        summary: 'Crear planilla CCSS',
        security: [['sanctum' => []]],
        tags: ['Planillas CCSS'],
        responses: [
            new OA\Response(response: 201, description: 'planilla CCSS creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', PlanillaCcss::class);

        $validator = Validator::make($request->all(), [
            'periodo_nomina_id' => 'required|exists:periodos_nomina,id',
            'periodo' => 'required|string|max:20',
            'fecha_generacion' => 'required|date',
            'numero_planilla' => 'nullable|string|max:50',
            'total_empleados' => 'required|integer|min:0',
            'total_salarios' => 'required|numeric|min:0',
            'total_cuota_obrera' => 'required|numeric|min:0',
            'total_cuota_patronal' => 'required|numeric|min:0',
            'total_a_pagar' => 'required|numeric|min:0',
            'estado' => 'required|string|in:borrador,enviada,pagada',
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $planilla = PlanillaCcss::create([
            'empresa_id' => auth('sanctum')->user()->empresa_id,
            'periodo_nomina_id' => $request->get('periodo_nomina_id'),
            'periodo' => $request->get('periodo'),
            'fecha_generacion' => $request->get('fecha_generacion'),
            'numero_planilla' => $request->get('numero_planilla'),
            'total_empleados' => $request->get('total_empleados'),
            'total_salarios' => $request->get('total_salarios'),
            'total_cuota_obrera' => $request->get('total_cuota_obrera'),
            'total_cuota_patronal' => $request->get('total_cuota_patronal'),
            'total_a_pagar' => $request->get('total_a_pagar'),
            'estado' => $request->get('estado', 'borrador'),
            'notas' => $request->get('notas'),
        ]);

        $this->flushCache();

        return response()->json([
            'message' => 'Planilla CCSS creada exitosamente',
            'data' => $planilla
        ], 201);
    }

    /**
     * Display the specified resource.
     */
        #[OA\Get(
        path: '/api/planilla-ccss/{id}',
        summary: 'Obtener planilla CCSS',
        security: [['sanctum' => []]],
        tags: ['Planillas CCSS'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'planilla CCSS encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function show(PlanillaCcss $planillaCcss): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $planillaCcss);

        $planillaCcss->load(['periodoNomina', 'empresa']);

        return response()->json($planillaCcss);
    }

    /**
     * Update the specified resource in storage.
     */
        #[OA\Put(
        path: '/api/planilla-ccss/{id}',
        summary: 'Actualizar planilla CCSS',
        security: [['sanctum' => []]],
        tags: ['Planillas CCSS'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'planilla CCSS actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

    public function update(Request $request, PlanillaCcss $planillaCcss): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $planillaCcss);

        $validator = Validator::make($request->all(), [
            'fecha_presentacion' => 'nullable|date',
            'numero_planilla' => 'nullable|string|max:50',
            'total_empleados' => 'sometimes|integer|min:0',
            'total_salarios' => 'sometimes|numeric|min:0',
            'total_cuota_obrera' => 'sometimes|numeric|min:0',
            'total_cuota_patronal' => 'sometimes|numeric|min:0',
            'total_a_pagar' => 'sometimes|numeric|min:0',
            'archivo_xml' => 'nullable|string',
            'archivo_pdf' => 'nullable|string',
            'estado' => 'sometimes|string|in:borrador,enviada,pagada',
            'fecha_pago' => 'nullable|date',
            'notas' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'fecha_presentacion',
            'numero_planilla',
            'total_empleados',
            'total_salarios',
            'total_cuota_obrera',
            'total_cuota_patronal',
            'total_a_pagar',
            'archivo_xml',
            'archivo_pdf',
            'estado',
            'fecha_pago',
            'notas',
        ]);

        $planillaCcss->update($data);

        $this->flushCache();

        return response()->json([
            'message' => 'Planilla CCSS actualizada exitosamente',
            'data' => $planillaCcss
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
        #[OA\Delete(
        path: '/api/planilla-ccss/{id}',
        summary: 'Eliminar planilla CCSS',
        security: [['sanctum' => []]],
        tags: ['Planillas CCSS'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'planilla CCSS eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(PlanillaCcss $planillaCcss): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $planillaCcss);

        $planillaCcss->eliminado = true;
        $planillaCcss->save();

        $this->flushCache();

        return response()->json([
            'message' => 'Planilla CCSS eliminada exitosamente'
        ]);
    }
}
