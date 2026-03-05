<?php

namespace App\Services;

use App\Models\CuentaContable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * CuentaContableService - Servicio de Gestión del Plan de Cuentas
 *
 * Encapsula la lógica de negocio para cuentas contables:
 * - CRUD con filtrado multi-tenant (empresa_id)
 * - Estructura jerárquica (árbol de cuentas)
 * - Validaciones de eliminación (subcuentas, asientos)
 * - Filtrado para movimientos contables
 *
 * Refactorización FASE 8 - Service Layer Pattern
 */
class CuentaContableService
{
    /**
     * Listar cuentas contables con filtros opcionales
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(int $empresaId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas']);

        if (!empty($filtros['tipo_cuenta_id'])) {
            $query->where('tipo_cuenta_id', $filtros['tipo_cuenta_id']);
        }

        if (!empty($filtros['cuenta_padre_id'])) {
            $query->where('cuenta_padre_id', $filtros['cuenta_padre_id']);
        }

        if (isset($filtros['principales']) && $filtros['principales'] == 1) {
            $query->whereNull('cuenta_padre_id');
        }

        if (!empty($filtros['codigo'])) {
            $query->where('codigo', 'like', "%{$filtros['codigo']}%");
        }

        if (isset($filtros['permite_movimientos'])) {
            $query->where('permite_movimientos', $filtros['permite_movimientos']);
        }

        $sortBy = $filtros['sort_by'] ?? 'codigo';
        $sortOrder = $filtros['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Crear una nueva cuenta contable
     *
     * @param int $empresaId
     * @param array<string, mixed> $data
     * @return CuentaContable
     */
    public function crear(int $empresaId, array $data): CuentaContable
    {
        $data['empresa_id'] = $empresaId;

        $cuenta = CuentaContable::create($data);

        return $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);
    }

    /**
     * Obtener cuenta contable por ID (scoped a empresa)
     *
     * @param int $empresaId
     * @param int $id
     * @return CuentaContable
     */
    public function obtener(int $empresaId, int $id): CuentaContable
    {
        return CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['cuentaPadre', 'tipoCuenta', 'subcuentas', 'asientos'])
            ->firstOrFail();
    }

    /**
     * Actualizar una cuenta contable existente
     *
     * @param int $empresaId
     * @param int $id
     * @param array<string, mixed> $data
     * @return CuentaContable
     */
    public function actualizar(int $empresaId, int $id, array $data): CuentaContable
    {
        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $cuenta->update($data);

        return $cuenta->load(['cuentaPadre', 'tipoCuenta', 'subcuentas']);
    }

    /**
     * Eliminar cuenta contable (soft delete)
     *
     * Valida que no tenga subcuentas ni asientos contables asociados.
     *
     * @param int $empresaId
     * @param int $id
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(int $empresaId, int $id): bool
    {
        $cuenta = CuentaContable::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        if ($cuenta->subcuentas()->where('eliminado', 0)->count() > 0) {
            throw ValidationException::withMessages([
                'cuenta' => 'No se puede eliminar una cuenta contable que tiene subcuentas asociadas',
            ]);
        }

        if ($cuenta->asientos()->count() > 0) {
            throw ValidationException::withMessages([
                'cuenta' => 'No se puede eliminar una cuenta contable que tiene asientos contables registrados',
            ]);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);

        return true;
    }

    /**
     * Obtener árbol jerárquico de cuentas contables
     *
     * @param int $empresaId
     * @return Collection
     */
    public function arbol(int $empresaId): Collection
    {
        return CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->whereNull('cuenta_padre_id')
            ->with(['subcuentas' => function ($query) {
                $query->where('eliminado', 0)->with('subcuentas');
            }])
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Obtener cuentas que permiten movimientos directos (para asientos)
     *
     * @param int $empresaId
     * @return Collection
     */
    public function paraMovimientos(int $empresaId): Collection
    {
        return CuentaContable::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('permite_movimientos', 1)
            ->orderBy('codigo')
            ->get();
    }
}
