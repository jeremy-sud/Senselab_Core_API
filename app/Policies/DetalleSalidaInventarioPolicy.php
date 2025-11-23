<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\DetalleSalidaInventario;

/**
 * DetalleSalidaInventarioPolicy - Gestión de autorización para DetalleSalidaInventario
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DetalleSalidaInventarioPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'detalle_salida_inventario';
}
