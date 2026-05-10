<?php

namespace App\Policies;

use App\Models\CuentaPorPagar;

/**
 * CuentaPorPagarPolicy - Gestión de autorización para CuentaPorPagar
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class CuentaPorPagarPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'cuentas_por_pagar';
}
