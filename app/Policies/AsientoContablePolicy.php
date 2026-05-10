<?php

namespace App\Policies;

use App\Models\AsientoContable;

/**
 * AsientoContablePolicy - Gestión de autorización para AsientoContable
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class AsientoContablePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'asientos_contables';
}
