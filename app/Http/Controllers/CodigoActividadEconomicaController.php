<?php

namespace App\Http\Controllers;

use App\Models\CodigoActividadEconomica;
use App\Http\Requests\StoreCodigoActividadEconomicaRequest;
use App\Http\Requests\UpdateCodigoActividadEconomicaRequest;
use App\Http\Resources\CodigoActividadEconomicaResource;
use Illuminate\Http\Request;

class CodigoActividadEconomicaController extends Controller
{
    public function index(Request $request)
    {
        $query = CodigoActividadEconomica::query();

        // Filtro por activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria_principal', $request->categoria);
        }

        // Búsqueda por código o descripción
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('categoria_principal', 'like', "%{$search}%");
            });
        }

        $codigos = $query->orderBy('codigo')->paginate($request->per_page ?? 15);

        return CodigoActividadEconomicaResource::collection($codigos);
    }

    public function store(StoreCodigoActividadEconomicaRequest $request)
    {
        $codigo = CodigoActividadEconomica::create($request->validated());

        return new CodigoActividadEconomicaResource($codigo);
    }

    public function show(CodigoActividadEconomica $codigoActividadEconomica)
    {
        return new CodigoActividadEconomicaResource($codigoActividadEconomica);
    }

    public function update(UpdateCodigoActividadEconomicaRequest $request, CodigoActividadEconomica $codigoActividadEconomica)
    {
        $codigoActividadEconomica->update($request->validated());

        return new CodigoActividadEconomicaResource($codigoActividadEconomica);
    }

    public function destroy(CodigoActividadEconomica $codigoActividadEconomica)
    {
        $codigoActividadEconomica->delete();

        return response()->json(['message' => 'Código de actividad eliminado correctamente'], 200);
    }
}
