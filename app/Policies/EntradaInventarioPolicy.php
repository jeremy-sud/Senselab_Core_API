<?php

namespace App\Policies;

use App\Models\EntradaInventario;

/**
 * EntradaInventarioPolicy - Gestión de autorización para EntradaInventario
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class EntradaInventarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'entrada_inventario';
}
