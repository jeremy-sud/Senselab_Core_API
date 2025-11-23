<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Configuracion;

/**
 * ConfiguracionPolicy - Gestión de autorización para Configuracion
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ConfiguracionPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'configuracion';
}
