<?php

namespace App\Policies;

use App\Models\HorarioRuta;

/**
 * HorarioRutaPolicy - Gestión de autorización para HorarioRuta
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class HorarioRutaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'horario_ruta';
}
