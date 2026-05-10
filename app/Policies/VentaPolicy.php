<?php

namespace App\Policies;

use App\Models\Venta;

/**
 * VentaPolicy - Gestión de autorización para Venta
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class VentaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'ventas';
}
