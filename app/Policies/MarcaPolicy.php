<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Marca;

/**
 * MarcaPolicy - Gestión de autorización para Marca
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MarcaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'marca';
}
