<?php

namespace App\Services;

use App\Events\ClienteCreadoEvent;
use App\Models\Cliente;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar Clientes
 *
 * Encapsula la lógica de negocio para clientes
 */
class ClienteService
{
    /**
     * Listar clientes con filtros y paginación
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Cliente::with('empresa');

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('numero_identificacion', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['tipo_identificacion'])) {
            $query->porTipoIdentificacion($filtros['tipo_identificacion']);
        }

        if (!empty($filtros['activos'])) {
            $query->activos();
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Crear un nuevo cliente
     *
     * @param array<string, mixed> $data
     * @return Cliente
     */
    public function crear(array $data): Cliente
    {
        $cliente = Cliente::create($data);
        $cliente->load('empresa');

        ClienteCreadoEvent::dispatch($cliente->empresa_id, [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'numero_identificacion' => $cliente->numero_identificacion,
            'email' => $cliente->email,
        ]);

        return $cliente;
    }

    /**
     * Obtener cliente por ID con relaciones
     *
     * @param int $id
     * @return Cliente
     */
    public function obtener(int $id): Cliente
    {
        return Cliente::with([
            'empresa',
            'ventas' => function ($query) {
                $query->latest()->limit(10);
            },
            'cuentasPorCobrar' => function ($query) {
                $query->where('estado', 'pendiente');
            }
        ])->findOrFail($id);
    }

    /**
     * Actualizar un cliente existente
     *
     * @param Cliente $cliente
     * @param array<string, mixed> $data
     * @return Cliente
     */
    public function actualizar(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);
        $cliente->load('empresa');
        return $cliente;
    }

    /**
     * Eliminar un cliente (soft delete)
     *
     * @param Cliente $cliente
     * @return bool
     */
    public function eliminar(Cliente $cliente): bool
    {
        $cliente->update([
            'activo' => false,
            'eliminado' => true,
        ]);
        return true;
    }

    /**
     * Calcular saldo pendiente de cliente
     *
     * @param Cliente $cliente
     * @return float
     */
    public function calcularSaldoPendiente(Cliente $cliente): float
    {
        return (float) (
            $cliente->cuentas_cobrar()->sum('monto_total') -
            $cliente->cuentas_cobrar()->sum('monto_pagado')
        );
    }
}
