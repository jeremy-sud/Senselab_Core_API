<?php

namespace App\Policies;

use App\Models\Producto;

/**
 * ProductoPolicy - Gestión de autorización para Producto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ProductoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'productos';
}
