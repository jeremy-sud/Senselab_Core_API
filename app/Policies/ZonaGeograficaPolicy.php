<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\ZonaGeografica;

/**
 * ZonaGeograficaPolicy - Gestión de autorización para ZonaGeografica
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ZonaGeograficaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    // Ajuste: usar guion para coincidir con Str::slug aplicado a permisos
    protected string $permission = 'zona-geografica';
}
