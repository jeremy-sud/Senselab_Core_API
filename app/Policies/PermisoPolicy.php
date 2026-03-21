<?php

namespace App\Policies;

use App\Models\Permiso;

/**
 * PermisoPolicy - Gestión de autorización para Permiso
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PermisoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'permisos';
}
