<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\TipoCliente;

/**
 * TipoClientePolicy - Gestión de autorización para TipoCliente
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoClientePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'tipo_cliente';
}
