<?php

namespace App\Policies;

use App\Models\ModeloBus;

/**
 * ModeloBusPolicy - Gestión de autorización para ModeloBus
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ModeloBusPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'modelo_bus';
}
