<?php

namespace App\Policies;

use App\Models\FormaPago;

/**
 * FormaPagoPolicy - Gestión de autorización para FormaPago
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class FormaPagoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'forma_pago';
}
