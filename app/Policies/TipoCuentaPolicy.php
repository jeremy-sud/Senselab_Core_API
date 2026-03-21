<?php

namespace App\Policies;

use App\Models\TipoCuenta;

/**
 * TipoCuentaPolicy - Gestión de autorización para TipoCuenta
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoCuentaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'tipo_cuenta';
}
