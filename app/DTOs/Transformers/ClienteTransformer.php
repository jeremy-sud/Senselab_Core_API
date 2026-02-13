<?php

namespace App\DTOs\Transformers;

use App\Models\Cliente;

/**
 * Transformer para convertir Cliente a array de respuesta
 *
 * Fecha de creación: 12 de febrero de 2026
 */
class ClienteTransformer
{
    /**
     * Transformar un Cliente a array
     */
    public static function transform(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'cedula_juridica' => $cliente->cedula_juridica,
            'email' => $cliente->email,
            'telefono' => $cliente->telefono,
            'direccion' => $cliente->direccion,
            'ciudad' => $cliente->ciudad,
            'provincia' => $cliente->provincia,
            'razon_social' => $cliente->razon_social,
            'activo' => (bool) $cliente->activo,
            'empresa_id' => $cliente->empresa_id,
            'saldo_pendiente' => (float) ($cliente->cuentas_cobrar()->sum('monto_total') -
                                          $cliente->cuentas_cobrar()->sum('monto_pagado')),
            'created_at' => $cliente->created_at?->toIso8601String(),
            'updated_at' => $cliente->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transformar colección de Clientes a array
     */
    public static function collection(iterable $clientes): array
    {
        $result = [];
        foreach ($clientes as $cliente) {
            $result[] = self::transform($cliente);
        }
        return $result;
    }
}
