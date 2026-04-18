<?php

namespace App\Services;

use App\Models\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar la relación Rol-Usuario.
 *
 * Encapsula la lógica de negocio para asignación de roles
 * a usuarios, incluyendo asignación masiva con transacciones.
 */
class RolUsuarioService
{
    /**
     * @param array<string, mixed> $filtros
     */
    public function listar(int $empresaId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RolUsuario::where('activo', 1)->where('eliminado', 0)
            ->whereHas('usuario', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        if (!empty($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }

        if (!empty($filtros['rol_id'])) {
            $query->where('rol_id', $filtros['rol_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function asignar(array $data): RolUsuario
    {
        return RolUsuario::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function actualizar(RolUsuario $rolUsuario, array $data): RolUsuario
    {
        $rolUsuario->update($data);

        return $rolUsuario;
    }

    public function eliminar(RolUsuario $rolUsuario): void
    {
        $rolUsuario->update(['eliminado' => 1, 'activo' => 0]);
    }

    /**
     * @return Collection<int, RolUsuario>
     */
    public function rolesPorUsuario(int $empresaId, int $usuarioId): Collection
    {
        Usuario::where('empresa_id', $empresaId)->findOrFail($usuarioId);

        return RolUsuario::where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->where('eliminado', 0)
            ->get();
    }

    /**
     * @return Collection<int, RolUsuario>
     */
    public function usuariosPorRol(int $empresaId, int $rolId): Collection
    {
        return RolUsuario::where('rol_id', $rolId)
            ->where('activo', 1)
            ->where('eliminado', 0)
            ->whereHas('usuario', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();
    }

    /**
     * @param array<int, int> $roles
     * @return array<int, RolUsuario>
     */
    public function asignarRoles(int $empresaId, int $usuarioId, array $roles): array
    {
        Usuario::where('empresa_id', $empresaId)->findOrFail($usuarioId);

        $rolesAsignados = [];

        DB::transaction(function () use ($usuarioId, $roles, &$rolesAsignados) {
            RolUsuario::where('usuario_id', $usuarioId)
                ->update(['activo' => 0]);

            foreach ($roles as $rolId) {
                $rolesAsignados[] = RolUsuario::updateOrCreate(
                    [
                        'usuario_id' => $usuarioId,
                        'rol_id' => $rolId,
                    ],
                    [
                        'activo' => 1,
                        'eliminado' => 0,
                    ]
                );
            }
        });

        return $rolesAsignados;
    }
}
