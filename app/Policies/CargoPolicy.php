<?php

namespace App\Policies;

use App\Models\Cargo;

/**
 * CargoPolicy - Gestión de autorización para Cargo
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CargoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'cargo';
}
