<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PagoCuentaCobrar;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagoCuentaCobrarPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pagos_cuentas_cobrar.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PagoCuentaCobrar $pago): bool
    {
        return $user->hasPermissionTo('pagos_cuentas_cobrar.show') &&
               $user->empresa_id === $pago->cuentaPorCobrar->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pagos_cuentas_cobrar.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PagoCuentaCobrar $pago): bool
    {
        // No se permite editar pagos ya aplicados
        if ($pago->estado === 'Aplicado') {
            return false;
        }

        return $user->hasPermissionTo('pagos_cuentas_cobrar.update') &&
               $user->empresa_id === $pago->cuentaPorCobrar->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PagoCuentaCobrar $pago): bool
    {
        // Solo se pueden anular pagos, no eliminar (trazabilidad)
        // Se valida que no hayan pasado más de X días desde el pago
        $diasLimite = 30;
        $diasTranscurridos = now()->diffInDays($pago->fecha_pago);

        if ($diasTranscurridos > $diasLimite) {
            return false;
        }

        return $user->hasPermissionTo('pagos_cuentas_cobrar.destroy') &&
               $user->empresa_id === $pago->cuentaPorCobrar->empresa_id;
    }
}
