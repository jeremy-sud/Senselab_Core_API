<?php

namespace App\Services;

use App\DTOs\API\ComprobanteElectronicoCreateDTO;
use App\Events\FacturaEmitidaEvent;
use App\Models\ComprobanteElectronicoFe;
use Illuminate\Pagination\LengthAwarePaginator;

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
    public function listar(int $perPage = 15): LengthAwarePaginator
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
        $estadoAnterior = $comprobante->estado;
        $comprobante->estado = $nuevoEstado;
        $comprobante->save();
        $comprobante = $comprobante->fresh() ?? $comprobante;

        $estadosEmision = ['aceptado', 'emitido', 'enviado'];
        if (in_array($nuevoEstado, $estadosEmision, true) && !in_array($estadoAnterior, $estadosEmision, true)) {
            FacturaEmitidaEvent::dispatch($comprobante->empresa_id, [
                'comprobante_id' => $comprobante->id,
                'clave_numerica' => $comprobante->clave_numerica ?? null,
                'tipo_comprobante' => $comprobante->tipo_comprobante ?? null,
                'estado' => $nuevoEstado,
                'venta_id' => $comprobante->venta_id ?? null,
            ]);
        }

        return $comprobante;
    }

    /**
     * Validar clave numérica
     */
    public function validarClaveNumerica(string $clave): bool
    {
        return strlen($clave) === 50;
    }
}
