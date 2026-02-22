<?php

namespace App\Policies;

use App\Models\DeduccionLegal;

/**
 * DeduccionLegalPolicy - Gestión de autorización para DeduccionLegal
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DeduccionLegalPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'deduccion_legal';
}
