<?php

namespace App\Policies;

use App\Models\CuentaBancaria;

/**
 * CuentaBancariaPolicy - Gestión de autorización para CuentaBancaria
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class CuentaBancariaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'cuentas_bancarias';
}
