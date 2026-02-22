<?php

namespace App\Policies;

use App\Models\Rol;

/**
 * RolPolicy - Gestión de autorización para Rol
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class RolPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'roles';
}
