<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\FeCertificadoDigital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** @extends BaseService<FeCertificadoDigital> */
class FeCertificadoDigitalService extends BaseService
{
    protected string $modelClass = FeCertificadoDigital::class;

    protected array $searchFields = ['nombre', 'numero_serie', 'emisor'];

    protected array $defaultRelations = ['empresa'];

    protected string $defaultOrderBy = 'created_at';
    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<FeCertificadoDigital> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }

        if (!empty($filtros['ambiente'])) {
            $query->where('ambiente', $filtros['ambiente']);
        }

        if (!empty($filtros['solo_vigentes'])) {
            $query->where('fecha_vencimiento', '>=', now());
        }

        if (!empty($filtros['dias_vencimiento'])) {
            $dias = (int) $filtros['dias_vencimiento'];
            $query->whereDate('fecha_vencimiento', '<=', now()->addDays($dias))
                ->whereDate('fecha_vencimiento', '>=', now());
        }
    }

    /**
     * Si se marca como activo, desactiva otros del mismo ambiente.
     *
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void
    {
        if ($data['activo'] ?? false) {
            FeCertificadoDigital::where('empresa_id', $data['empresa_id'])
                ->where('ambiente', $data['ambiente'])
                ->update(['activo' => false]);
        }
    }

    /**
     * Si se activa, desactiva otros del mismo ambiente.
     *
     * @param FeCertificadoDigital $model
     * @param array<string, mixed> $data
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        /** @var FeCertificadoDigital $model */
        if (($data['activo'] ?? false) && !$model->activo) {
            FeCertificadoDigital::where('empresa_id', $model->empresa_id)
                ->where('ambiente', $model->ambiente)
                ->where('id', '!=', $model->id)
                ->update(['activo' => false]);
        }
    }

    /**
     * Elimina el certificado y su archivo físico.
     */
    public function eliminar(Model $model): bool
    {
        /** @var FeCertificadoDigital $model */
        if ($model->ruta_archivo && Storage::disk('private')->exists($model->ruta_archivo)) {
            Storage::disk('private')->delete($model->ruta_archivo);
        }

        $model->delete();

        return true;
    }

    /**
     * Activa un certificado y desactiva otros del mismo ambiente.
     */
    public function activar(FeCertificadoDigital $certificado, int $empresaId): FeCertificadoDigital
    {
        if ($certificado->fecha_vencimiento < now()) {
            throw new BusinessException('No se puede activar un certificado vencido');
        }

        FeCertificadoDigital::where('empresa_id', $empresaId)
            ->where('ambiente', $certificado->ambiente)
            ->where('id', '!=', $certificado->id)
            ->update(['activo' => false]);

        $certificado->update(['activo' => true]);

        return $certificado;
    }

    public function desactivar(FeCertificadoDigital $certificado): FeCertificadoDigital
    {
        $certificado->update(['activo' => false]);

        return $certificado;
    }

    public function obtenerActivo(int $empresaId, string $ambiente = 'produccion'): ?FeCertificadoDigital
    {
        /** @var FeCertificadoDigital|null */
        return FeCertificadoDigital::where('empresa_id', $empresaId)
            ->where('ambiente', $ambiente)
            ->where('activo', true)
            ->where('fecha_vencimiento', '>=', now())
            ->first();
    }

    /**
     * @return Collection<int, FeCertificadoDigital>
     */
    public function proximosVencer(int $empresaId, int $dias = 30): Collection
    {
        /** @var Collection<int, FeCertificadoDigital> */
        return FeCertificadoDigital::where('empresa_id', $empresaId)
            ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias))
            ->whereDate('fecha_vencimiento', '>=', now())
            ->orderBy('fecha_vencimiento')
            ->get();
    }
}
