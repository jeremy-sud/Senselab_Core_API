<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBancario;
use App\Http\Requests\StoreMovimientoBancarioRequest;
use App\Http\Requests\UpdateMovimientoBancarioRequest;
use App\Http\Resources\MovimientoBancarioResource;
use Illuminate\Http\Request;

class MovimientoBancarioController extends Controller
{
    public function index(Request $request)
    {
        $query = MovimientoBancario::with(['cuentaBancaria', 'empresa', 'asientoContable']);

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('cuenta_bancaria_id')) {
            $query->where('cuenta_bancaria_id', $request->cuenta_bancaria_id);
        }

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->filled('conciliado')) {
            $query->where('conciliado', $request->boolean('conciliado'));
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_referencia', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('beneficiario', 'like', "%{$search}%");
            });
        }

        $movimientos = $query->latest('fecha_movimiento')->paginate($request->per_page ?? 15);

        return MovimientoBancarioResource::collection($movimientos);
    }

    public function store(StoreMovimientoBancarioRequest $request)
    {
        $movimiento = MovimientoBancario::create($request->validated());
        $movimiento->load(['cuentaBancaria', 'empresa', 'asientoContable']);

        return new MovimientoBancarioResource($movimiento);
    }

    public function show(MovimientoBancario $movimientoBancario)
    {
        $movimientoBancario->load(['cuentaBancaria', 'empresa', 'asientoContable']);

        return new MovimientoBancarioResource($movimientoBancario);
    }

    public function update(UpdateMovimientoBancarioRequest $request, MovimientoBancario $movimientoBancario)
    {
        $movimientoBancario->update($request->validated());
        $movimientoBancario->load(['cuentaBancaria', 'empresa', 'asientoContable']);

        return new MovimientoBancarioResource($movimientoBancario);
    }

    public function destroy(MovimientoBancario $movimientoBancario)
    {
        $movimientoBancario->delete();

        return response()->json(['message' => 'Movimiento bancario eliminado correctamente'], 200);
    }
}
