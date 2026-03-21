<?php

namespace App\Policies;

use App\Models\PlanillaCcss;

/**
 * PlanillaCcssPolicy - Gestión de autorización para PlanillaCcss
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PlanillaCcssPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'planilla_ccss';
}
