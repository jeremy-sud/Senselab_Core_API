<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Venta;

/**
 * VentaPolicy - Gestión de autorización para Venta
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class VentaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'ventas';
}
