<?php

namespace App\Http\Controllers;

use App\Models\DeduccionLegal;
use App\Http\Requests\StoreDeduccionLegalRequest;
use App\Http\Requests\UpdateDeduccionLegalRequest;
use App\Http\Resources\DeduccionLegalResource;
use Illuminate\Http\Request;

class DeduccionLegalController extends Controller
{
    public function index(Request $request)
    {
        $query = DeduccionLegal::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        if ($request->filled('es_obligatoria')) {
            $query->where('es_obligatoria', $request->boolean('es_obligatoria'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        $deducciones = $query->orderBy('codigo')->paginate($request->per_page ?? 15);

        return DeduccionLegalResource::collection($deducciones);
    }

    public function store(StoreDeduccionLegalRequest $request)
    {
        $deduccion = DeduccionLegal::create($request->validated());

        return new DeduccionLegalResource($deduccion);
    }

    public function show(DeduccionLegal $deduccionLegal)
    {
        return new DeduccionLegalResource($deduccionLegal);
    }

    public function update(UpdateDeduccionLegalRequest $request, DeduccionLegal $deduccionLegal)
    {
        $deduccionLegal->update($request->validated());

        return new DeduccionLegalResource($deduccionLegal);
    }

    public function destroy(DeduccionLegal $deduccionLegal)
    {
        $deduccionLegal->delete();

        return response()->json(['message' => 'Deducción legal eliminada correctamente'], 200);
    }
}
