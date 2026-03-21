<?php

namespace App\Policies;

use App\Models\OrdenCompra;

/**
 * OrdenCompraPolicy - Gestión de autorización para OrdenCompra
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class OrdenCompraPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'ordenes_compra';
}
