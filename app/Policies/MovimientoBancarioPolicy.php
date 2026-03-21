<?php

namespace App\Policies;

use App\Models\MovimientoBancario;

/**
 * MovimientoBancarioPolicy - Gestión de autorización para MovimientoBancario
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MovimientoBancarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'movimientos_bancarios';
}
