<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\FeCertificadoDigital;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeCertificadoDigitalPolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'facturacion_electronica';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermission('ver-facturacion_electronica');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $certificado = $model;
        if (!$user->hasPermission('ver-facturacion_electronica')) {
            return false;
        }

        return $certificado->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermission('crear-facturacion_electronica') 
            && ($user->hasRole('Administrador') || $user->hasRole('Gerente'));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $certificado = $model;
        if (!$user->hasPermission('editar-facturacion_electronica')) {
            return false;
        }

        if (!$user->hasAnyRole(['Administrador', 'Gerente'])) {
            return false;
        }

        return $certificado->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $certificado = $model;
        if (!$user->hasPermission('eliminar-facturacion_electronica')) {
            return false;
        }

        // Solo administradores pueden eliminar certificados
        if (!$user->hasRole('Administrador')) {
            return false;
        }

        // No se puede eliminar el certificado activo
        if ($certificado->activo) {
            return false;
        }

        return $certificado->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $certificado = $model;
        return $user->hasPermission('editar-facturacion_electronica')
            && $user->hasRole('Administrador')
            && $certificado->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $certificado = $model;
        return $user->hasPermission('eliminar-facturacion_electronica')
            && $user->hasRole('Administrador')
            && $certificado->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can activate the certificate.
     */
    public function activar(Usuario $user, FeCertificadoDigital $certificado): bool
    {
        if (!$user->hasPermission('editar-facturacion_electronica')) {
            return false;
        }

        if (!$user->hasRole('Administrador') && !$user->hasRole('Gerente')) {
            return false;
        }

        // Verificar que no esté vencido
        if ($certificado->fecha_vencimiento < now()) {
            return false;
        }

        return $certificado->empresa_id === $user->empresa_id;
    }
}
