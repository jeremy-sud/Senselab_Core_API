<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\CodigoActividadEconomica;

/**
 * CodigoActividadEconomicaPolicy - Gestión de autorización para CodigoActividadEconomica
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CodigoActividadEconomicaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'codigo_actividad_economica';
}
