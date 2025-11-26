<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\ComprobanteElectronicoFe;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComprobanteElectronicoFePolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'facturacion_electronica';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermissionTo('ver-facturacion_electronica');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $comprobante = $model;
        if (!$user->hasPermissionTo('ver-facturacion_electronica')) {
            return false;
        }

        // Verificar que el comprobante pertenece a la empresa del usuario
        return $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermissionTo('crear-facturacion_electronica');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $comprobante = $model;
        if (!$user->hasPermissionTo('editar-facturacion_electronica')) {
            return false;
        }

        // No se puede editar un comprobante aceptado por Hacienda
        if ($comprobante->estado === 'aceptado') {
            return false;
        }

        return $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $comprobante = $model;
        if (!$user->hasPermissionTo('eliminar-facturacion_electronica')) {
            return false;
        }

        // Solo se pueden eliminar comprobantes rechazados o en borrador
        if (!in_array($comprobante->estado, ['borrador', 'rechazado'])) {
            return false;
        }

        return $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $comprobante = $model;
        return $user->hasPermissionTo('editar-facturacion_electronica') 
            && $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $comprobante = $model;
        return $user->hasPermissionTo('eliminar-facturacion_electronica')
            && $user->hasRole('Administrador')
            && $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can reenviar the comprobante to Hacienda.
     */
    public function reenviar(Usuario $user, ComprobanteElectronicoFe $comprobante): bool
    {
        if (!$user->hasPermissionTo('editar-facturacion_electronica')) {
            return false;
        }

        // Solo se pueden reenviar comprobantes rechazados o con error
        if (!in_array($comprobante->estado, ['rechazado', 'error'])) {
            return false;
        }

        return $comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can anular the comprobante.
     */
    public function anular(Usuario $user, ComprobanteElectronicoFe $comprobante): bool
    {
        if (!$user->hasPermissionTo('crear-facturacion_electronica')) {
            return false;
        }

        // Solo se pueden anular comprobantes aceptados
        if ($comprobante->estado !== 'aceptado') {
            return false;
        }

        return $comprobante->empresa_id === $user->empresa_id;
    }
}
