<?php

namespace App\Policies;

use App\Models\Usuario;

/**
 * UsuarioPolicy - Gestión de autorización para Usuario
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class UsuarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'usuarios';
}
