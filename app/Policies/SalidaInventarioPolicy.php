<?php

namespace App\Policies;

use App\Models\SalidaInventario;

/**
 * SalidaInventarioPolicy - Gestión de autorización para SalidaInventario
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class SalidaInventarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'salida_inventario';
}
