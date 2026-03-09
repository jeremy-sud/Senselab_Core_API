<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\AuditoriaActividad;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditoriaActividadPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo admin y auditores pueden ver logs de auditoría
        return $user->hasPermission('auditoria_actividades.index') &&
               $user->hasAnyRole(['Administrador', 'Auditor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AuditoriaActividad $auditoria): bool
    {
        // Solo admin y auditores pueden ver detalles de auditoría
        return $user->hasPermission('auditoria_actividades.show') &&
               $user->hasAnyRole(['Administrador', 'Auditor']) &&
               $user->empresa_id === $auditoria->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los registros de auditoría se crean automáticamente por el sistema
        // No se permite creación manual
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AuditoriaActividad $auditoria): bool
    {
        // Los registros de auditoría son INMUTABLES
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AuditoriaActividad $auditoria): bool
    {
        // Los registros de auditoría NUNCA se eliminan (trazabilidad legal)
        return false;
    }

    /**
     * Determinar si el usuario puede exportar auditorías
     */
    public function exportar(User $user): bool
    {
        return $user->hasPermission('auditoria_actividades.exportar') &&
               $user->hasAnyRole(['Administrador', 'Auditor']);
    }

    /**
     * Determinar si el usuario puede ver estadísticas
     */
    public function estadisticas(User $user): bool
    {
        return $user->hasPermission('auditoria_actividades.estadisticas') &&
               $user->hasAnyRole(['Administrador', 'Auditor']);
    }
}
