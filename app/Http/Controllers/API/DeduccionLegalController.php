<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeduccionLegal;
use App\Http\Resources\DeduccionLegalResource;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador para deducciones legales de nómina
 * Gestiona CCSS, INS, LPT y otras deducciones obligatorias
 */
class DeduccionLegalController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['deducciones-legales', 'nomina', 'catalogos'];
    protected int $cacheTTL = 7200; // 2 horas - catálogo semi-estable

    /**
     * Listar deducciones legales
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DeduccionLegal::class);

        $cacheKey = $this->getCacheKey('index', [
            'activa' => $request->input('activa'),
            'tipo' => $request->input('tipo'),
            'obligatoria' => $request->input('obligatoria'),
            'per_page' => $request->input('per_page', 20)
        ]);

        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = DeduccionLegal::query();

            if ($request->filled('activa')) {
                $query->where('activa', $request->boolean('activa'));
            }

            if ($request->filled('tipo')) {
                $query->porTipo($request->tipo);
            }

            if ($request->filled('obligatoria')) {
                $query->where('es_obligatoria', $request->boolean('obligatoria'));
            }

            $deducciones = $query->orderBy('id')->paginate($request->input('per_page', 20));
            
            return DeduccionLegalResource::collection($deducciones);
        });
    }

    /**
     * Crear deducción legal
     */
    public function store(Request $request): DeduccionLegalResource
    {
        $this->authorize('create', DeduccionLegal::class);

        $validated = $request->validate([
            'codigo' => 'required|string|max:20|unique:deducciones_legales',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'tipo' => 'required|string|max:50',
            'porcentaje_base' => 'nullable|numeric|min:0|max:100',
            'monto_fijo' => 'nullable|numeric|min:0',
            'aplica_sobre' => 'nullable|string|max:50',
            'es_obligatoria' => 'boolean',
            'activa' => 'boolean'
        ]);

        $deduccion = DeduccionLegal::create($validated);

        $this->flushCache();

        return new DeduccionLegalResource($deduccion);
    }

    /**
     * Mostrar deducción específica
     */
    public function show(DeduccionLegal $deduccionLegal): DeduccionLegalResource
    {
        $this->authorize('view', $deduccionLegal);
        return new DeduccionLegalResource($deduccionLegal);
    }

    /**
     * Actualizar deducción legal
     */
    public function update(Request $request, DeduccionLegal $deduccionLegal): DeduccionLegalResource
    {
        $this->authorize('update', $deduccionLegal);

        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:deducciones_legales,codigo,' . $deduccionLegal->id,
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'tipo' => 'sometimes|string|max:50',
            'porcentaje_base' => 'nullable|numeric|min:0|max:100',
            'monto_fijo' => 'nullable|numeric|min:0',
            'aplica_sobre' => 'nullable|string|max:50',
            'es_obligatoria' => 'boolean',
            'activa' => 'boolean'
        ]);

        $deduccionLegal->update($validated);

        $this->flushCache();

        return new DeduccionLegalResource($deduccionLegal);
    }

    /**
     * Eliminar deducción legal
     */
    public function destroy(DeduccionLegal $deduccionLegal): JsonResponse
    {
        $this->authorize('delete', $deduccionLegal);

        $deduccionLegal->update(['eliminado' => true, 'activa' => false]);

        $this->flushCache();

        return response()->json(['success' => true, 'message' => 'Deducción eliminada exitosamente']);
    }
}
