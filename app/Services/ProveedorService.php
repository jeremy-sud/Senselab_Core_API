<?php

namespace App\Services;

use App\DTOs\API\ProveedorCreateDTO;
use App\DTOs\API\ProveedorUpdateDTO;
use App\Models\Proveedor;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar Proveedores
 *
 * Encapsula la lógica de negocio para proveedores
 * Fecha de creación: 12 de febrero de 2026
 */
class ProveedorService
{
    /**
     * Crear un nuevo proveedor
     */
    public function crear(ProveedorCreateDTO $dto): Proveedor
    {
        return Proveedor::create($dto->toArray());
    }

    /**
     * Actualizar un proveedor existente
     */
    public function actualizar(Proveedor $proveedor, ProveedorUpdateDTO $dto): Proveedor
    {
        $proveedor->update($dto->toArray());
        return $proveedor->fresh() ?? $proveedor;
    }

    /**
     * Eliminar un proveedor
     */
    public function eliminar(Proveedor $proveedor): bool
    {
        return (bool) $proveedor->delete();
    }

    /**
     * Obtener proveedor por ID
     */
    public function obtener(int $proveedorId): ?Proveedor
    {
        return Proveedor::find($proveedorId);
    }

    /**
     * Listar proveedores con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return Proveedor::paginate($perPage);
    }

    /**
     * Buscar proveedores
     */
    public function buscar(string $termino, int $perPage = 15): LengthAwarePaginator
    {
        return Proveedor::where('nombre', 'like', "%{$termino}%")
            ->orWhere('cedula_juridica', 'like', "%{$termino}%")
            ->orWhere('email', 'like', "%{$termino}%")
            ->paginate($perPage);
    }

    /**
     * Obtener proveedores activos
     */
    public function activos(int $perPage = 15): LengthAwarePaginator
    {
        return Proveedor::where('activo', true)->paginate($perPage);
    }

    /**
     * Calcular saldo pendiente de proveedor
     */
    public function calcularSaldoPendiente(Proveedor $proveedor): float
    {
        return (float) (
            $proveedor->cuentas_pagar()->sum('monto_total') -
            $proveedor->cuentas_pagar()->sum('monto_pagado')
        );
    }
}
