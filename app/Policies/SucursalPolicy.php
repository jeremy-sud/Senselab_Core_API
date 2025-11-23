<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Sucursal;

/**
 * SucursalPolicy - Gestión de autorización para Sucursal
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class SucursalPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'sucursales';
}
