<?php

namespace App\Policies;

use App\Models\TiqueteDetalle;

/**
 * TiqueteDetallePolicy - Gestión de autorización para TiqueteDetalle
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TiqueteDetallePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'tiquete_detalle';
}
