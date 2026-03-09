<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\ConsecutivoFe;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsecutivoFePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('consecutivos_fe.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ConsecutivoFe $consecutivo): bool
    {
        return $user->hasPermission('consecutivos_fe.show') &&
               $user->empresa_id === $consecutivo->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // CRÍTICO - Solo administradores pueden crear consecutivos DGT
        return $user->hasPermission('consecutivos_fe.store') &&
               $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConsecutivoFe $consecutivo): bool
    {
        // CRÍTICO - Solo se permiten actualizaciones limitadas
        // No se puede modificar: empresa_id, sucursal_id, tipo_comprobante, consecutivo_inicial
        // Solo se permite: consecutivo_final (expansión de rango)
        
        return $user->hasPermission('consecutivos_fe.update') &&
               $user->empresa_id === $consecutivo->empresa_id &&
               $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConsecutivoFe $consecutivo): bool
    {
        // CRÍTICO - Nunca se eliminan físicamente (trazabilidad DGT)
        // Solo soft delete y únicamente si NO se han usado consecutivos (consecutivo_actual === consecutivo_inicial)
        
        if ($consecutivo->consecutivo_actual > $consecutivo->consecutivo_inicial) {
            return false; // Ya se usaron consecutivos - NO eliminar
        }

        return $user->hasPermission('consecutivos_fe.destroy') &&
               $user->empresa_id === $consecutivo->empresa_id &&
               $user->hasRole('Administrador');
    }

    /**
     * Determinar si el usuario puede obtener el siguiente consecutivo
     */
    public function obtenerSiguiente(User $user, ConsecutivoFe $consecutivo): bool
    {
        // Cualquier usuario con permisos de facturación puede obtener consecutivos
        return $user->hasPermission('consecutivos_fe.siguiente') &&
               $user->empresa_id === $consecutivo->empresa_id &&
               $consecutivo->activo === true;
    }
}
