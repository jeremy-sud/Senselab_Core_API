<?php

namespace App\Policies;

use App\Models\CuentaPorCobrar;

/**
 * CuentaPorCobrarPolicy - Gestión de autorización para CuentaPorCobrar
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CuentaPorCobrarPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'cuentas_por_cobrar';
}
