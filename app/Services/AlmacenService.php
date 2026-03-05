<?php

namespace App\Services;

use App\Models\Almacen;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * AlmacenService - Servicio de Gestión de Almacenes/Bodegas
 *
 * Encapsula la lógica de negocio para almacenes:
 * - CRUD operations
 * - Gestión de almacén principal (es_principal)
 * - Validaciones de eliminación
 * - Filtrado por empresa y sucursal
 *
 * Refactorización FASE 8 - Service Layer Pattern
 */
class AlmacenService
{
    /**
     * Listar almacenes con filtros opcionales
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Almacen::with(['empresa', 'sucursal']);

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['sucursal_id'])) {
            $query->where('sucursal_id', $filtros['sucursal_id']);
        }

        if (!empty($filtros['activos'])) {
            $query->where('activo', true);
        }

        return $query->orderBy('id', 'asc')->paginate($perPage);
    }

    /**
     * Crear un nuevo almacén
     *
     * Si es_principal = true, desmarca otros almacenes principales de la sucursal.
     *
     * @param array<string, mixed> $data
     * @return Almacen
     */
    public function crear(array $data): Almacen
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['es_principal'])) {
                $this->desmarcarPrincipales($data['sucursal_id']);
            }

            $almacen = Almacen::create($data);

            return $almacen->load(['empresa', 'sucursal']);
        });
    }

    /**
     * Obtener almacén por ID con relaciones
     *
     * @param int $id
     * @return Almacen
     */
    public function obtener(int $id): Almacen
    {
        return Almacen::with(['empresa', 'sucursal'])->findOrFail($id);
    }

    /**
     * Actualizar un almacén existente
     *
     * Si se marca como principal, desmarca otros de la misma sucursal.
     *
     * @param Almacen $almacen
     * @param array<string, mixed> $data
     * @return Almacen
     */
    public function actualizar(Almacen $almacen, array $data): Almacen
    {
        return DB::transaction(function () use ($almacen, $data) {
            if (isset($data['es_principal']) && $data['es_principal']) {
                Almacen::where('sucursal_id', $almacen->sucursal_id)
                    ->where('id', '!=', $almacen->id)
                    ->update(['es_principal' => false]);
            }

            $almacen->update($data);

            return $almacen->load(['empresa', 'sucursal']);
        });
    }

    /**
     * Eliminar un almacén (soft delete)
     *
     * No permite eliminar el almacén principal.
     *
     * @param Almacen $almacen
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(Almacen $almacen): bool
    {
        if ($almacen->es_principal) {
            throw ValidationException::withMessages([
                'almacen' => 'No se puede eliminar el almacén principal',
            ]);
        }

        $almacen->update([
            'activo' => false,
            'eliminado' => true,
        ]);

        return true;
    }

    /**
     * Desmarcar todos los almacenes principales de una sucursal
     *
     * @param int $sucursalId
     * @return void
     */
    private function desmarcarPrincipales(int $sucursalId): void
    {
        Almacen::where('sucursal_id', $sucursalId)
            ->update(['es_principal' => false]);
    }
}
