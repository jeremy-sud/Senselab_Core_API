<?php

namespace App\Policies;

use App\Models\PeriodoNomina;

/**
 * PeriodoNominaPolicy - Gestión de autorización para PeriodoNomina
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PeriodoNominaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'periodo_nomina';
}
