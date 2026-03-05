<?php

namespace App\Services;

use App\Models\Proveedor;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ProveedorService - Servicio de Gestión de Proveedores
 *
 * Encapsula la lógica de negocio para proveedores:
 * - CRUD operations
 * - Búsqueda multi-campo (nombre, nombre_comercial, número, email)
 * - Carga de relaciones (órdenes recientes, cuentas pendientes)
 * - Soft delete
 *
 * Fecha de creación: 12 de febrero de 2026
 * Refactorizado FASE 8 - Service Layer Pattern
 */
class ProveedorService
{
    /**
     * Listar proveedores con filtros opcionales
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Proveedor::with('empresa');

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('numero_identificacion', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['activos'])) {
            $query->where('activo', true);
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Crear un nuevo proveedor
     *
     * @param array<string, mixed> $data
     * @return Proveedor
     */
    public function crear(array $data): Proveedor
    {
        $proveedor = Proveedor::create($data);

        return $proveedor->load('empresa');
    }

    /**
     * Obtener proveedor por ID con relaciones detalladas
     *
     * Incluye últimas 10 órdenes de compra y cuentas por pagar pendientes.
     *
     * @param int $id
     * @return Proveedor
     */
    public function obtener(int $id): Proveedor
    {
        return Proveedor::with([
            'empresa',
            'ordenesCompra' => function ($query) {
                $query->latest()->limit(10);
            },
            'cuentasPorPagar' => function ($query) {
                $query->where('estado', 'pendiente');
            },
        ])->findOrFail($id);
    }

    /**
     * Actualizar un proveedor existente
     *
     * @param Proveedor $proveedor
     * @param array<string, mixed> $data
     * @return Proveedor
     */
    public function actualizar(Proveedor $proveedor, array $data): Proveedor
    {
        $proveedor->update($data);

        return $proveedor->load('empresa');
    }

    /**
     * Eliminar un proveedor (soft delete)
     *
     * @param Proveedor $proveedor
     * @return bool
     */
    public function eliminar(Proveedor $proveedor): bool
    {
        $proveedor->update([
            'activo' => false,
            'eliminado' => true,
        ]);

        return true;
    }

    /**
     * Calcular saldo pendiente de proveedor
     *
     * @param Proveedor $proveedor
     * @return float
     */
    public function calcularSaldoPendiente(Proveedor $proveedor): float
    {
        return (float) (
            $proveedor->cuentasPorPagar()->sum('monto_total') -
            $proveedor->cuentasPorPagar()->sum('monto_pagado')
        );
    }
}
