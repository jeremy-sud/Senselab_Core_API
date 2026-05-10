<?php

namespace App\Policies;

use App\Models\Proveedor;

/**
 * ProveedorPolicy - Gestión de autorización para Proveedor
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
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
