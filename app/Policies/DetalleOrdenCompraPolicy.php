<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\DetalleOrdenCompra;
use Illuminate\Auth\Access\HandlesAuthorization;

class DetalleOrdenCompraPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('detalle_ordenes_compra.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DetalleOrdenCompra $detalleOrden): bool
    {
        return $user->hasPermissionTo('detalle_ordenes_compra.show') &&
               $user->empresa_id === $detalleOrden->ordenCompra->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('detalle_ordenes_compra.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DetalleOrdenCompra $detalleOrden): bool
    {
        // No se permite editar detalles de órdenes ya aprobadas o recibidas
        if ($detalleOrden->ordenCompra && 
            in_array($detalleOrden->ordenCompra->estado, ['Aprobada', 'Recibida', 'Cancelada'])) {
            return false;
        }

        return $user->hasPermissionTo('detalle_ordenes_compra.update') &&
               $user->empresa_id === $detalleOrden->ordenCompra->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DetalleOrdenCompra $detalleOrden): bool
    {
        // No se permite eliminar detalles de órdenes ya aprobadas o recibidas
        if ($detalleOrden->ordenCompra && 
            in_array($detalleOrden->ordenCompra->estado, ['Aprobada', 'Recibida', 'Cancelada'])) {
            return false;
        }

        return $user->hasPermissionTo('detalle_ordenes_compra.destroy') &&
               $user->empresa_id === $detalleOrden->ordenCompra->empresa_id;
    }
}
