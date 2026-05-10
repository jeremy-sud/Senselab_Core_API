<?php

namespace App\Policies;

use App\Models\UnidadMedida;

/**
 * UnidadMedidaPolicy - Gestión de autorización para UnidadMedida
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class UnidadMedidaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'unidad_medida';
}
