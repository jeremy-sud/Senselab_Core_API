<?php

namespace App\Policies;

use App\Models\Almacen;

/**
 * AlmacenPolicy - Gestión de autorización para Almacen
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class AlmacenPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'almacenes';
}
