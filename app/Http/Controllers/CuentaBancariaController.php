<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Http\Requests\StoreCuentaBancariaRequest;
use App\Http\Requests\UpdateCuentaBancariaRequest;
use App\Http\Resources\CuentaBancariaResource;
use Illuminate\Http\Request;

class CuentaBancariaController extends Controller
{
    public function index(Request $request)
    {
        $query = CuentaBancaria::with('empresa');

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        if ($request->filled('tipo_cuenta')) {
            $query->where('tipo_cuenta', $request->tipo_cuenta);
        }

        if ($request->filled('moneda')) {
            $query->where('moneda', $request->moneda);
        }

        if ($request->filled('activa')) {
            $query->where('activa', $request->boolean('activa'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('banco', 'like', "%{$search}%")
                  ->orWhere('iban', 'like', "%{$search}%")
                  ->orWhere('numero_cuenta', 'like', "%{$search}%");
            });
        }

        $cuentas = $query->orderBy('banco')->paginate($request->per_page ?? 15);

        return CuentaBancariaResource::collection($cuentas);
    }

    public function store(StoreCuentaBancariaRequest $request)
    {
        $cuenta = CuentaBancaria::create($request->validated());
        $cuenta->load('empresa');

        return new CuentaBancariaResource($cuenta);
    }

    public function show($id)
    {
        // Buscar sin el scope global para evitar problemas con route model binding
        $cuentaBancaria = CuentaBancaria::withoutGlobalScope('tenant')
            ->with('empresa')
            ->findOrFail($id);

        return new CuentaBancariaResource($cuentaBancaria);
    }

    public function update(UpdateCuentaBancariaRequest $request, $id)
    {
        // Buscar sin el scope global para evitar problemas con route model binding
        $cuentaBancaria = CuentaBancaria::withoutGlobalScope('tenant')
            ->findOrFail($id);
        
        $cuentaBancaria->update($request->validated());
        
        $cuentaBancaria->load('empresa');

        return new CuentaBancariaResource($cuentaBancaria);
    }

    public function destroy($id)
    {
        $cuentaBancaria = CuentaBancaria::withoutGlobalScope('tenant')
            ->findOrFail($id);
            
        $cuentaBancaria->delete();

        return response()->json(['message' => 'Cuenta bancaria eliminada correctamente'], 200);
    }
}
