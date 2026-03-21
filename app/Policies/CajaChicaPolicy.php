<?php

namespace App\Policies;

use App\Models\CajaChica;

/**
 * CajaChicaPolicy - Gestión de autorización para CajaChica
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CajaChicaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'cajas_chicas';
}
