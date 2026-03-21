<?php

namespace App\Policies;

use App\Models\DetalleEntradaInventario;

/**
 * DetalleEntradaInventarioPolicy - Gestión de autorización para DetalleEntradaInventario
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DetalleEntradaInventarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'detalle_entrada_inventario';
}
