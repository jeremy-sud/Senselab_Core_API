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
     * Los permisos esperados son: ver-tipos-clientes, crear-tipos-clientes, etc.
     * 
     * @var string
     */
    protected string $permission = 'tipos-clientes';
}
