<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\ComprobanteRecibidoElectronico;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/** @extends BaseService<ComprobanteRecibidoElectronico> */
class ComprobanteRecibidoElectronicoService extends BaseService
{
    protected string $modelClass = ComprobanteRecibidoElectronico::class;

    protected array $defaultRelations = ['proveedor', 'entradaInventario', 'usuarioConfirmacion'];

    protected string $defaultOrderBy = 'fecha_recepcion_sistema';
    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<ComprobanteRecibidoElectronico> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }
    }

    /**
     * Asigna valores por defecto para comprobante nuevo.
     *
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void
    {
        $data['moneda'] = $data['moneda'] ?? 'CRC';
        $data['estado_hacienda'] = 'Procesando';
        $data['confirmado_usuario'] = 0;
    }

    /**
     * No permite modificar comprobantes confirmados.
     *
     * @param ComprobanteRecibidoElectronico $model
     * @param array<string, mixed> $data
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        /** @var ComprobanteRecibidoElectronico $model */
        if ($model->confirmado_usuario == 1) {
            throw new BusinessException('No se puede modificar un comprobante confirmado');
        }
    }

    /**
     * No permite eliminar comprobantes confirmados; hace delete real.
     */
    public function eliminar(Model $model): bool
    {
        /** @var ComprobanteRecibidoElectronico $model */
        if ($model->confirmado_usuario == 1) {
            throw new BusinessException('No se puede eliminar un comprobante confirmado');
        }

        $model->delete();

        return true;
    }

    /**
     * Obtiene un comprobante por empresa e ID.
     */
    public function obtenerPorEmpresa(int $empresaId, int $id): ComprobanteRecibidoElectronico
    {
        /** @var ComprobanteRecibidoElectronico */
        return ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->with($this->getRelationsForDetail())
            ->findOrFail($id);
    }

    public function confirmar(ComprobanteRecibidoElectronico $comprobante, int $usuarioId): ComprobanteRecibidoElectronico
    {
        if ($comprobante->confirmado_usuario == 1) {
            throw new BusinessException('El comprobante ya fue confirmado');
        }

        $comprobante->update([
            'confirmado_usuario' => 1,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId,
        ]);

        /** @var ComprobanteRecibidoElectronico */
        return $comprobante->fresh(['proveedor', 'usuarioConfirmacion']);
    }

    public function rechazar(ComprobanteRecibidoElectronico $comprobante, int $usuarioId): ComprobanteRecibidoElectronico
    {
        $comprobante->update([
            'confirmado_usuario' => 2,
            'fecha_confirmacion_usuario' => now(),
            'usuario_confirmacion_id' => $usuarioId,
        ]);

        /** @var ComprobanteRecibidoElectronico */
        return $comprobante->fresh(['proveedor', 'usuarioConfirmacion']);
    }

    /**
     * @return LengthAwarePaginator<int, ComprobanteRecibidoElectronico>
     */
    public function porProveedor(int $empresaId, int $proveedorId): LengthAwarePaginator
    {
        return ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->with(['proveedor', 'entradaInventario'])
            ->orderByDesc('fecha_emision_comprobante')
            ->paginate(15);
    }

    /**
     * @return Collection<int, ComprobanteRecibidoElectronico>
     */
    public function pendientes(int $empresaId): Collection
    {
        /** @var Collection<int, ComprobanteRecibidoElectronico> */
        return ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->where('confirmado_usuario', 0)
            ->with(['proveedor'])
            ->orderBy('fecha_recepcion_sistema')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function resumenPorEstadoHacienda(int $empresaId): \Illuminate\Support\Collection
    {
        return ComprobanteRecibidoElectronico::where('empresa_id', $empresaId)
            ->selectRaw('estado_hacienda, COUNT(*) as total_comprobantes, SUM(total_comprobante) as monto_total')
            ->groupBy('estado_hacienda')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function actualizarRespuestaHacienda(ComprobanteRecibidoElectronico $comprobante, array $data): ComprobanteRecibidoElectronico
    {
        $comprobante->update([
            'xml_respuesta_hacienda' => $data['xml_respuesta_hacienda'],
            'estado_hacienda' => $data['estado_hacienda'],
            'mensaje_hacienda' => $data['mensaje_hacienda'],
            'fecha_respuesta_hacienda' => now(),
        ]);

        return $comprobante;
    }
}
