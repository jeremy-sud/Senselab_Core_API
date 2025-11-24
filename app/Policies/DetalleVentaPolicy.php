<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\DetalleVenta;
use Illuminate\Auth\Access\HandlesAuthorization;

class DetalleVentaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('detalle_ventas.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DetalleVenta $detalleVenta): bool
    {
        return $user->hasPermissionTo('detalle_ventas.show') &&
               $user->empresa_id === $detalleVenta->venta->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('detalle_ventas.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DetalleVenta $detalleVenta): bool
    {
        // No se permite editar detalles de ventas ya facturadas
        if ($detalleVenta->venta && $detalleVenta->venta->estado === 'Facturada') {
            return false;
        }

        return $user->hasPermissionTo('detalle_ventas.update') &&
               $user->empresa_id === $detalleVenta->venta->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DetalleVenta $detalleVenta): bool
    {
        // No se permite eliminar detalles de ventas ya facturadas
        if ($detalleVenta->venta && $detalleVenta->venta->estado === 'Facturada') {
            return false;
        }

        return $user->hasPermissionTo('detalle_ventas.destroy') &&
               $user->empresa_id === $detalleVenta->venta->empresa_id;
    }
}
