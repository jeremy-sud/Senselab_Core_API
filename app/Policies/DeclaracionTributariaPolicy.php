<?php

namespace App\Policies;

use App\Models\DeclaracionTributaria;

/**
 * DeclaracionTributariaPolicy - Gestión de autorización para DeclaracionTributaria
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DeclaracionTributariaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'declaraciones_tributarias';
}
