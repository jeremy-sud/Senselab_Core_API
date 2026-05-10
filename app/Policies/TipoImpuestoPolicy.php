<?php

namespace App\Policies;

use App\Models\TipoImpuesto;

/**
 * TipoImpuestoPolicy - Gestión de autorización para TipoImpuesto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class TipoImpuestoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'tipo_impuesto';
}
