<?php

namespace App\Services;

use App\DTOs\API\ClienteCreateDTO;
use App\DTOs\API\ClienteUpdateDTO;
use App\Models\Cliente;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar Clientes
 *
 * Encapsula la lógica de negocio para clientes
 * Fecha de creación: 12 de febrero de 2026
 */
class ClienteService
{
    /**
     * Crear un nuevo cliente
     */
    public function crear(ClienteCreateDTO $dto): Cliente
    {
        return Cliente::create($dto->toArray());
    }

    /**
     * Actualizar un cliente existente
     */
    public function actualizar(Cliente $cliente, ClienteUpdateDTO $dto): Cliente
    {
        $cliente->update($dto->toArray());
        return $cliente->fresh() ?? $cliente;
    }

    /**
     * Eliminar un cliente
     */
    public function eliminar(Cliente $cliente): bool
    {
        return (bool) $cliente->delete();
    }

    /**
     * Obtener cliente por ID
     */
    public function obtener(int $clienteId): ?Cliente
    {
        return Cliente::find($clienteId);
    }

    /**
     * Listar clientes con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return Cliente::paginate($perPage);
    }

    /**
     * Buscar clientes
     */
    public function buscar(string $termino, int $perPage = 15): LengthAwarePaginator
    {
        return Cliente::where('nombre', 'like', "%{$termino}%")
            ->orWhere('cedula_juridica', 'like', "%{$termino}%")
            ->orWhere('email', 'like', "%{$termino}%")
            ->paginate($perPage);
    }

    /**
     * Obtener clientes activos
     */
    public function activos(int $perPage = 15): LengthAwarePaginator
    {
        return Cliente::where('activo', true)->paginate($perPage);
    }

    /**
     * Calcular saldo pendiente de cliente
     */
    public function calcularSaldoPendiente(Cliente $cliente): float
    {
        return (float) (
            $cliente->cuentas_cobrar()->sum('monto_total') -
            $cliente->cuentas_cobrar()->sum('monto_pagado')
        );
    }
}
