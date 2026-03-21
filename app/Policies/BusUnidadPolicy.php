<?php

namespace App\Policies;

use App\Models\BusUnidad;

/**
 * BusUnidadPolicy - Gestión de autorización para BusUnidad
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class BusUnidadPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'bus_unidad';
}
