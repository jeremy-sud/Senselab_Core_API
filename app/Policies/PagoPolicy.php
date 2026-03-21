<?php

namespace App\Policies;

use App\Models\Pago;

/**
 * PagoPolicy - Gestión de autorización para Pago
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PagoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'pago';
}
