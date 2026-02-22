<?php

namespace App\Policies;

use App\Models\Ruta;

/**
 * RutaPolicy - Gestión de autorización para Ruta
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class RutaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'ruta';
}
