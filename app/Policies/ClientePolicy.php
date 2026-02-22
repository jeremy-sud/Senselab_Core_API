<?php

namespace App\Policies;

use App\Models\Cliente;

/**
 * ClientePolicy - Gestión de autorización para Cliente
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ClientePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'clientes';
}
