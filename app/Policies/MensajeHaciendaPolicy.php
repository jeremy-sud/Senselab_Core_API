<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\MensajeHacienda;

/**
 * MensajeHaciendaPolicy - Gestión de autorización para MensajeHacienda
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MensajeHaciendaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'mensaje_hacienda';
}
