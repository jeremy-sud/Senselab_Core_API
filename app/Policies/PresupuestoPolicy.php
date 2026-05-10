<?php

namespace App\Policies;

use App\Models\Presupuesto;

/**
 * PresupuestoPolicy - Gestión de autorización para Presupuesto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class PresupuestoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'presupuesto';
}
