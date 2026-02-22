<?php

namespace App\Policies;

/**
 * InventarioPolicy - Gestión de autorización para Inventario
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class InventarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'inventario';
}
