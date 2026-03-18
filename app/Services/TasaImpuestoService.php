<?php

namespace App\Services;

use App\Models\TasaImpuesto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<TasaImpuesto> */
class TasaImpuestoService extends BaseService
{
    protected string $modelClass = TasaImpuesto::class;

    protected array $defaultRelations = ['tipoImpuesto'];

    protected string $defaultOrderBy = 'fecha_inicio_vigencia';

    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<TasaImpuesto> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('eliminado', false);

        if (!empty($filtros['tipo_impuesto_id'])) {
            $query->where('tipo_impuesto_id', $filtros['tipo_impuesto_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }

        if (!empty($filtros['vigentes'])) {
            $now = Carbon::now();
            $query->where('fecha_inicio_vigencia', '<=', $now)
                ->where(fn (Builder $q) => $q->whereNull('fecha_fin_vigencia')
                    ->orWhere('fecha_fin_vigencia', '>=', $now));
        }
    }

    public function obtener(int $id): Model
    {
        return TasaImpuesto::where('eliminado', false)
            ->with($this->getRelationsForDetail())
            ->findOrFail($id);
    }

    /**
     * Obtener la tasa vigente para un tipo de impuesto en una fecha determinada.
     */
    public function vigente(int $tipoImpuestoId, ?Carbon $fecha = null): ?TasaImpuesto
    {
        $fecha ??= Carbon::now();

        return TasaImpuesto::where('tipo_impuesto_id', $tipoImpuestoId)
            ->where('eliminado', false)
            ->where('activo', true)
            ->where('fecha_inicio_vigencia', '<=', $fecha)
            ->where(fn (Builder $q) => $q->whereNull('fecha_fin_vigencia')
                ->orWhere('fecha_fin_vigencia', '>=', $fecha))
            ->with('tipoImpuesto')
            ->first();
    }

    /**
     * Todas las tasas vigentes a la fecha actual.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TasaImpuesto>
     */
    public function vigentesActuales(): \Illuminate\Database\Eloquent\Collection
    {
        $now = Carbon::now();

        return TasaImpuesto::where('eliminado', false)
            ->where('activo', true)
            ->where('fecha_inicio_vigencia', '<=', $now)
            ->where(fn (Builder $q) => $q->whereNull('fecha_fin_vigencia')
                ->orWhere('fecha_fin_vigencia', '>=', $now))
            ->with('tipoImpuesto')
            ->get();
    }

    /**
     * Histórico completo de tasas para un tipo de impuesto.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TasaImpuesto>
     */
    public function historico(int $tipoImpuestoId): \Illuminate\Database\Eloquent\Collection
    {
        return TasaImpuesto::where('tipo_impuesto_id', $tipoImpuestoId)
            ->where('eliminado', false)
            ->with('tipoImpuesto')
            ->orderBy('fecha_inicio_vigencia', 'desc')
            ->get();
    }
}
