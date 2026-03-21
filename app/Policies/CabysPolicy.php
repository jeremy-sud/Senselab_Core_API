<?php

namespace App\Policies;

use App\Models\Cabys;

/**
 * CabysPolicy - Gestión de autorización para Cabys
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CabysPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'cabys';
}
