<?php

namespace App\Policies;

use App\Models\Empresa;

/**
 * EmpresaPolicy - Gestión de autorización para Empresa
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class EmpresaPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'empresas';
}
