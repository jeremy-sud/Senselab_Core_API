<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\LogAccesoSistema;

/**
 * LogAccesoSistemaPolicy - Gestión de autorización para LogAccesoSistema
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class LogAccesoSistemaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'log_acceso_sistema';
}
