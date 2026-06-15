<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\NominaEmpleado;
use Illuminate\Auth\Access\HandlesAuthorization;

class NominaEmpleadoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Información sensible - requiere permisos de RRHH
        return $user->hasPermission('nomina_empleados.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NominaEmpleado $nomina): bool
    {
        // Empleados solo pueden ver su propia nómina
        if ($user->empleado && $user->empleado->id === $nomina->empleado_id) {
            return true;
        }

        // Personal RRHH puede ver todas las nóminas de su empresa
        return $user->hasPermission('nomina_empleados.show') &&
               $nomina->empleado &&
               $user->empresa_id === $nomina->empleado->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo RRHH puede crear nóminas
        return $user->hasPermission('nomina_empleados.store') &&
               $user->hasAnyRole(['Administrador', 'RRHH']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NominaEmpleado $nomina): bool
    {
        // No se permite editar nóminas ya pagadas
        if ($nomina->estado === 'Pagada') {
            return false;
        }

        // Solo RRHH puede editar
        return $user->hasPermission('nomina_empleados.update') &&
               $nomina->empleado &&
               $user->empresa_id === $nomina->empleado->empresa_id &&
               $user->hasAnyRole(['Administrador', 'RRHH']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NominaEmpleado $nomina): bool
    {
        // No se pueden eliminar nóminas ya pagadas (trazabilidad legal)
        if ($nomina->estado === 'Pagada') {
            return false;
        }

        // Solo administradores pueden eliminar nóminas
        return $user->hasPermission('nomina_empleados.destroy') &&
               $nomina->empleado &&
               $user->empresa_id === $nomina->empleado->empresa_id &&
               $user->hasRole('Administrador');
    }
}
