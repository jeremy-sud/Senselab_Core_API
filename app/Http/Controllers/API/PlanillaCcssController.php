<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PlanillaCcss;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlanillaCcssController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['planillas-ccss', 'nomina', 'ccss'];
    protected int $cacheTTL = 3600; // 1 hora - planillas estables una vez generadas

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', PlanillaCcss::class);

        $cacheKey = $this->getCacheKey([
            'estado' => $request->get('estado'),
            'periodo' => $request->get('periodo'),
            'fecha_desde' => $request->get('fecha_desde'),
            'fecha_hasta' => $request->get('fecha_hasta'),
            'per_page' => $request->get('per_page', 20),
        ]);

        $planillas = $this->cacheQueryIfEnabled($cacheKey, function () use ($request) {
            $query = PlanillaCcss::query()
                ->where('empresa_id', auth()->user()->empresa_id)
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
    public function store(Request $request)
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
            'empresa_id' => auth()->user()->empresa_id,
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
    public function show(PlanillaCcss $planillaCcss)
    {
        $this->authorize('view', $planillaCcss);

        $planillaCcss->load(['periodoNomina', 'empresa']);

        return response()->json($planillaCcss);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlanillaCcss $planillaCcss)
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
    public function destroy(PlanillaCcss $planillaCcss)
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
