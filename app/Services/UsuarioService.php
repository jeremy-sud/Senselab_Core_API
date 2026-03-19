<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** @extends BaseService<Usuario> */
class UsuarioService extends BaseService
{
    protected string $modelClass = Usuario::class;

    protected array $searchFields = ['nombre', 'apellido1', 'email'];

    protected array $defaultRelations = ['roles', 'cargo', 'empresa'];

    /**
     * @param Builder<Usuario> $query
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

        if (!empty($filtros['cargo_id'])) {
            $query->where('cargo_id', $filtros['cargo_id']);
        }
    }

    /** @return array<int, string|array<string, \Closure>> */
    protected function getRelationsForDetail(): array
    {
        return ['roles.permisos', 'cargo', 'empresa'];
    }

    /**
     * @param array<string, mixed> $data
     * @return Usuario
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);

            $roles = $data['roles'] ?? null;
            unset($data['roles']);

            /** @var Usuario $usuario */
            $usuario = Usuario::create($data);

            if ($roles) {
                $usuario->roles()->attach($roles);
            }

            return $usuario->load($this->getRelationsForDetail());
        });
    }

    /**
     * @param Usuario $model
     * @param array<string, mixed> $data
     * @return Usuario
     */
    public function actualizar(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            if (isset($data['password'])) {
                $data['password_hash'] = Hash::make($data['password']);
                unset($data['password']);
            }

            $roles = $data['roles'] ?? null;
            unset($data['roles']);

            $model->update($data);

            if ($roles !== null) {
                $model->roles()->sync($roles);
            }

            return $model->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var Usuario $model */
        return DB::transaction(function () use ($model): bool {
            $model->eliminado = now();
            $model->activo = 0;
            $model->save();
            $model->tokens()->delete();

            return true;
        });
    }

    /** @param array<int, int> $roleIds */
    public function asignarRoles(Usuario $usuario, array $roleIds): Usuario
    {
        $usuario->roles()->sync($roleIds);

        /** @var Usuario */
        return $usuario->load('roles');
    }

    public function cambiarPassword(Usuario $usuario, string $passwordActual, string $passwordNueva): void
    {
        if (!Hash::check($passwordActual, $usuario->password_hash)) {
            throw new BusinessException('La contraseña actual es incorrecta');
        }

        $usuario->password_hash = Hash::make($passwordNueva);
        $usuario->save();
    }
}
