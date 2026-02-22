<?php

namespace App\Policies;

use App\Models\CuentaContable;

/**
 * CuentaContablePolicy - Gestión de autorización para CuentaContable
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CuentaContablePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'cuenta_contable';
}
