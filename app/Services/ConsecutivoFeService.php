<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\ConsecutivoFe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<ConsecutivoFe> */
class ConsecutivoFeService extends BaseService
{
    protected string $modelClass = ConsecutivoFe::class;

    protected array $defaultRelations = ['sucursal'];

    protected string $defaultOrderBy = 'tipo_documento_dgt';

    /**
     * @param Builder<ConsecutivoFe> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        $query->where('eliminado', 0);

        if (!empty($filtros['sucursal_id'])) {
            $query->where('sucursal_id', $filtros['sucursal_id']);
        }

        if (!empty($filtros['tipo_documento_dgt'])) {
            $query->where('tipo_documento_dgt', $filtros['tipo_documento_dgt']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
    }

    /**
     * @param Builder<ConsecutivoFe> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyOrdering(Builder $query, array $filtros): void
    {
        $query->orderBy('tipo_documento_dgt')->orderBy('prefijo');
    }

    /**
     * No permite modificar consecutivo_actual manualmente.
     *
     * @param ConsecutivoFe $model
     * @param array<string, mixed> $data
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        unset($data['consecutivo_actual']);
    }

    /**
     * Obtiene el siguiente consecutivo de forma thread-safe.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function obtenerSiguiente(int $empresaId, array $params): array
    {
        DB::beginTransaction();

        try {
            $query = ConsecutivoFe::where('empresa_id', $empresaId)
                ->where('tipo_documento_dgt', $params['tipo_documento_dgt'])
                ->where('estado', 'Activo')
                ->where('eliminado', 0)
                ->lockForUpdate();

            if (!empty($params['sucursal_id'])) {
                $query->where('sucursal_id', $params['sucursal_id']);
            }

            if (!empty($params['prefijo'])) {
                $query->where('prefijo', $params['prefijo']);
            }

            $consecutivo = $query->first();

            if (!$consecutivo) {
                DB::rollBack();
                throw new BusinessException('No hay consecutivo activo disponible para este tipo de documento');
            }

            $siguienteNumero = $consecutivo->consecutivo_actual;
            $consecutivo->increment('consecutivo_actual');

            DB::commit();

            return [
                'consecutivo_id' => $consecutivo->id,
                'tipo_documento_dgt' => $consecutivo->tipo_documento_dgt,
                'prefijo' => $consecutivo->prefijo,
                'numero' => $siguienteNumero,
                'consecutivo_completo' => $consecutivo->prefijo . str_pad((string) $siguienteNumero, 10, '0', STR_PAD_LEFT),
            ];
        } catch (BusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resetear(ConsecutivoFe $consecutivo, int $nuevoConsecutivo): ConsecutivoFe
    {
        $consecutivo->update(['consecutivo_actual' => $nuevoConsecutivo]);

        return $consecutivo;
    }

    /**
     * @return Collection<int, ConsecutivoFe>
     */
    public function porTipoDocumento(int $empresaId, string $tipoDocumentoDgt): Collection
    {
        /** @var Collection<int, ConsecutivoFe> */
        return ConsecutivoFe::where('empresa_id', $empresaId)
            ->where('tipo_documento_dgt', $tipoDocumentoDgt)
            ->where('eliminado', 0)
            ->orderBy('prefijo')
            ->get();
    }

    public function marcarAgotado(ConsecutivoFe $consecutivo): ConsecutivoFe
    {
        $consecutivo->update(['estado' => 'Agotado']);

        return $consecutivo;
    }

    public function activar(ConsecutivoFe $consecutivo): ConsecutivoFe
    {
        $consecutivo->update(['estado' => 'Activo', 'activo' => 1]);

        return $consecutivo;
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function resumenPorEstado(int $empresaId): \Illuminate\Support\Collection
    {
        return ConsecutivoFe::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();
    }
}
