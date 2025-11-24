<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\MovimientoCajaChica;
use Illuminate\Auth\Access\HandlesAuthorization;

class MovimientoCajaChicaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('movimientos_caja_chica.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MovimientoCajaChica $movimiento): bool
    {
        return $user->hasPermissionTo('movimientos_caja_chica.show') &&
               $user->empresa_id === $movimiento->cajaChica->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('movimientos_caja_chica.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MovimientoCajaChica $movimiento): bool
    {
        // No se permite editar movimientos de cajas cerradas o liquidadas
        if (in_array($movimiento->cajaChica->estado, ['Cerrada', 'Liquidada'])) {
            return false;
        }

        // Solo se pueden editar movimientos del mismo día
        if (!$movimiento->created_at->isToday()) {
            return false;
        }

        return $user->hasPermissionTo('movimientos_caja_chica.update') &&
               $user->empresa_id === $movimiento->cajaChica->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MovimientoCajaChica $movimiento): bool
    {
        // No se permite eliminar movimientos de cajas cerradas o liquidadas
        if (in_array($movimiento->cajaChica->estado, ['Cerrada', 'Liquidada'])) {
            return false;
        }

        // Solo se pueden anular movimientos del mismo día
        if (!$movimiento->created_at->isToday()) {
            return false;
        }

        return $user->hasPermissionTo('movimientos_caja_chica.destroy') &&
               $user->empresa_id === $movimiento->cajaChica->empresa_id &&
               $user->hasRole('admin|tesorero');
    }
}
