<?php

namespace App\Services;

use App\DTOs\API\ComprobanteElectronicoCreateDTO;
use App\Models\ComprobanteElectronicoFe;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para gestionar Comprobantes Electrónicos
 * 
 * Encapsula la lógica de negocio para comprobantes electrónicos
 * Fecha de creación: 12 de febrero de 2026
 */
class ComprobanteElectronicoService
{
    /**
     * Crear un nuevo comprobante electrónico
     */
    public function crear(ComprobanteElectronicoCreateDTO $dto): ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::create($dto->toArray());
    }

    /**
     * Obtener comprobante por ID
     */
    public function obtener(int $comprobanteId): ?ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::find($comprobanteId);
    }

    /**
     * Listar comprobantes con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return ComprobanteElectronicoFe::paginate($perPage);
    }

    /**
     * Comprobantes por venta
     */
    public function porVenta(int $ventaId): ?ComprobanteElectronicoFe
    {
        return ComprobanteElectronicoFe::where('venta_id', $ventaId)->first();
    }

    /**
     * Cambiar estado de comprobante
     */
    public function cambiarEstado(ComprobanteElectronicoFe $comprobante, string $nuevoEstado): ComprobanteElectronicoFe
    {
        $comprobante->estado = $nuevoEstado;
        $comprobante->save();
        return $comprobante->fresh();
    }

    /**
     * Validar clave numérica
     */
    public function validarClaveNumerica(string $clave): bool
    {
        return strlen($clave) === 50;
    }
}
