<?php

namespace App\Services;

use App\Models\RolPermiso;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Servicio para gestionar la relación Rol-Permiso.
 *
 * Encapsula la lógica de negocio para asignación, remoción
 * y sincronización de permisos a roles.
 */
class RolPermisoService
{
    /**
     * @param array<string, mixed> $filtros
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RolPermiso::query();

        if (!empty($filtros['rol_id'])) {
            $query->where('rol_id', $filtros['rol_id']);
        }

        if (!empty($filtros['permiso_id'])) {
            $query->where('permiso_id', $filtros['permiso_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', $filtros['activo']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function asignar(array $data): RolPermiso
    {
        $existente = RolPermiso::where('rol_id', $data['rol_id'])
            ->where('permiso_id', $data['permiso_id'])
            ->first();

        if ($existente) {
            throw new \App\Exceptions\BusinessException('Este permiso ya está asignado al rol');
        }

        return RolPermiso::create([
            'rol_id' => $data['rol_id'],
            'permiso_id' => $data['permiso_id'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function actualizar(RolPermiso $rolPermiso, array $data): RolPermiso
    {
        $rolPermiso->update($data);

        return $rolPermiso;
    }

    public function remover(int $rolId, int $permisoId): void
    {
        $rolPermiso = RolPermiso::where('rol_id', $rolId)
            ->where('permiso_id', $permisoId)
            ->first();

        if (!$rolPermiso) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('La relación rol-permiso no existe');
        }

        $rolPermiso->delete();
    }

    /**
     * @param int $rolId
     * @param array<int, int> $permisoIds
     * @return array{asignados: array<int, RolPermiso>, ya_existentes: array<int, int>}
     */
    public function asignarMultiples(int $rolId, array $permisoIds): array
    {
        $asignados = [];
        $yaExistentes = [];

        foreach ($permisoIds as $permisoId) {
            $existente = RolPermiso::where('rol_id', $rolId)
                ->where('permiso_id', $permisoId)
                ->first();

            if ($existente) {
                $yaExistentes[] = $permisoId;
            } else {
                $asignados[] = RolPermiso::create([
                    'rol_id' => $rolId,
                    'permiso_id' => $permisoId,
                ]);
            }
        }

        return ['asignados' => $asignados, 'ya_existentes' => $yaExistentes];
    }

    /**
     * @param array<int, int> $permisoIds
     */
    public function removerMultiples(int $rolId, array $permisoIds): int
    {
        return RolPermiso::where('rol_id', $rolId)
            ->whereIn('permiso_id', $permisoIds)
            ->delete();
    }

    /**
     * @return Collection<int, RolPermiso>
     */
    public function permisosPorRol(int $rolId): Collection
    {
        return RolPermiso::where('rol_id', $rolId)
            ->where('activo', 1)
            ->get();
    }

    /**
     * @return Collection<int, RolPermiso>
     */
    public function rolesPorPermiso(int $permisoId): Collection
    {
        return RolPermiso::where('permiso_id', $permisoId)
            ->where('activo', 1)
            ->get();
    }

    /**
     * @param array<int, int> $permisoIds
     * @return array<int, RolPermiso>
     */
    public function sincronizar(int $rolId, array $permisoIds): array
    {
        RolPermiso::where('rol_id', $rolId)->delete();

        $nuevos = [];
        foreach ($permisoIds as $permisoId) {
            $nuevos[] = RolPermiso::create([
                'rol_id' => $rolId,
                'permiso_id' => $permisoId,
            ]);
        }

        return $nuevos;
    }
}
