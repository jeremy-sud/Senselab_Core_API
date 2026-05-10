<?php

namespace App\Policies;

use App\Models\RetencionImpuesto;

/**
 * RetencionImpuestoPolicy - Gestión de autorización para RetencionImpuesto
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class RetencionImpuestoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'retenciones_impuestos';
}
