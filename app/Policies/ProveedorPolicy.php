<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Proveedor;

/**
 * ProveedorPolicy - Gestión de autorización para Proveedor
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ProveedorPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'proveedores';
}
