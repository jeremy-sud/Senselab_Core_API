<?php

namespace App\Services;

use App\Models\Empleado;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * EmpleadoService - Servicio de Gestión de Empleados
 *
 * Encapsula la lógica de negocio para empleados:
 * - CRUD operations con transacciones
 * - Búsqueda por nombre, apellido, identificación
 * - Filtrado por departamento, cargo, estado
 * - Soft delete estándar de Laravel
 *
 * Refactorización FASE 8 - Service Layer Pattern
 */
class EmpleadoService
{
    /**
     * Listar empleados activos con filtros opcionales
     *
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Empleado::with(['usuario', 'departamento', 'cargo'])
            ->activos();

        if (!empty($filtros['departamento_id'])) {
            $query->where('departamento_id', $filtros['departamento_id']);
        }

        if (!empty($filtros['cargo_id'])) {
            $query->where('cargo_id', $filtros['cargo_id']);
        }

        if (!empty($filtros['search'])) {
            $search = $filtros['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('primer_apellido', 'like', "%{$search}%")
                    ->orWhere('numero_documento', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('primer_apellido')
            ->orderBy('nombre')
            ->paginate($perPage);
    }

    /**
     * Crear un nuevo empleado
     *
     * @param array<string, mixed> $data
     * @return Empleado
     */
    public function crear(array $data): Empleado
    {
        return DB::transaction(function () use ($data) {
            $empleado = Empleado::create($data);

            return $empleado->load(['usuario', 'departamento', 'cargo']);
        });
    }

    /**
     * Obtener empleado por ID con relaciones
     *
     * @param int $id
     * @return Empleado
     */
    public function obtener(int $id): Empleado
    {
        return Empleado::with(['usuario', 'departamento', 'cargo'])->findOrFail($id);
    }

    /**
     * Actualizar un empleado existente
     *
     * @param Empleado $empleado
     * @param array<string, mixed> $data
     * @return Empleado
     */
    public function actualizar(Empleado $empleado, array $data): Empleado
    {
        return DB::transaction(function () use ($empleado, $data) {
            $empleado->update($data);

            return $empleado->fresh(['usuario', 'departamento', 'cargo']) ?? $empleado;
        });
    }

    /**
     * Eliminar un empleado (soft delete)
     *
     * @param Empleado $empleado
     * @return bool
     */
    public function eliminar(Empleado $empleado): bool
    {
        return (bool) $empleado->delete();
    }
}
