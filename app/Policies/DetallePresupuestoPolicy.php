<?php

namespace App\Policies;

use App\Models\DetallePresupuesto;

/**
 * DetallePresupuestoPolicy - Gestión de autorización para DetallePresupuesto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class DetallePresupuestoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'detalle_presupuesto';
}
