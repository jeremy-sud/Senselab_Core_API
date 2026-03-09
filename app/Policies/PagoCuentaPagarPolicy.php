<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\PagoCuentaPagar;
use Illuminate\Auth\Access\HandlesAuthorization;

class PagoCuentaPagarPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pagos_cuentas_pagar.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PagoCuentaPagar $pago): bool
    {
        return $user->hasPermission('pagos_cuentas_pagar.show') &&
               $user->empresa_id === $pago->cuentaPorPagar->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('pagos_cuentas_pagar.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PagoCuentaPagar $pago): bool
    {
        // No se permite editar pagos ya aplicados
        if ($pago->estado === 'Aplicado') {
            return false;
        }

        return $user->hasPermission('pagos_cuentas_pagar.update') &&
               $user->empresa_id === $pago->cuentaPorPagar->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PagoCuentaPagar $pago): bool
    {
        // Solo se pueden anular pagos, no eliminar (trazabilidad)
        // Se valida que no hayan pasado más de X días desde el pago
        $diasLimite = 30;
        $diasTranscurridos = now()->diffInDays($pago->fecha_pago);

        if ($diasTranscurridos > $diasLimite) {
            return false;
        }

        return $user->hasPermission('pagos_cuentas_pagar.destroy') &&
               $user->empresa_id === $pago->cuentaPorPagar->empresa_id;
    }
}
