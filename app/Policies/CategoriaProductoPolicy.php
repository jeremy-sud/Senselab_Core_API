<?php

namespace App\Policies;

use App\Models\CategoriaProducto;

/**
 * CategoriaProductoPolicy - Gestión de autorización para CategoriaProducto
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CategoriaProductoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'categorias_producto';
}
