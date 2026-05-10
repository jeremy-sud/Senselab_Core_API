<?php

namespace App\Policies;

use App\Models\DetalleAsiento;

/**
 * DetalleAsientoPolicy - Gestión de autorización para DetalleAsiento
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class DetalleAsientoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'detalle_asiento';
}
