<?php

namespace App\Http\Controllers;

use App\Models\DeclaracionTributaria;
use App\Http\Requests\StoreDeclaracionTributariaRequest;
use App\Http\Requests\UpdateDeclaracionTributariaRequest;
use App\Http\Resources\DeclaracionTributariaResource;
use Illuminate\Http\Request;

class DeclaracionTributariaController extends Controller
{
    public function index(Request $request)
    {
        $query = DeclaracionTributaria::with('empresa');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('tipo_declaracion')) {
            $query->where('tipo_declaracion', $request->tipo_declaracion);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo_fiscal', $request->periodo);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_presentacion', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_presentacion', '<=', $request->fecha_hasta);
        }

        $declaraciones = $query->latest('fecha_presentacion')->paginate($request->per_page ?? 15);

        return DeclaracionTributariaResource::collection($declaraciones);
    }

    public function store(StoreDeclaracionTributariaRequest $request)
    {
        $declaracion = DeclaracionTributaria::create($request->validated());
        $declaracion->load('empresa');

        return new DeclaracionTributariaResource($declaracion);
    }

    public function show(DeclaracionTributaria $declaracionTributaria)
    {
        $declaracionTributaria->load('empresa');

        return new DeclaracionTributariaResource($declaracionTributaria);
    }

    public function update(UpdateDeclaracionTributariaRequest $request, DeclaracionTributaria $declaracionTributaria)
    {
        $declaracionTributaria->update($request->validated());
        $declaracionTributaria->load('empresa');

        return new DeclaracionTributariaResource($declaracionTributaria);
    }

    public function destroy(DeclaracionTributaria $declaracionTributaria)
    {
        $declaracionTributaria->delete();

        return response()->json(['message' => 'Declaración eliminada correctamente'], 200);
    }
}
