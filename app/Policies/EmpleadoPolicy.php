<?php

namespace App\Policies;

use App\Models\Empleado;

/**
 * EmpleadoPolicy - Gestión de autorización para Empleado
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class EmpleadoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'empleados';
}
