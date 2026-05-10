<?php

namespace App\Policies;

use App\Models\TasaImpuesto;

/**
 * TasaImpuestoPolicy - Gestión de autorización para TasaImpuesto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class TasaImpuestoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'tasa_impuesto';
}
