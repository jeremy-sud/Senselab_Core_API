<?php

namespace App\Policies;

use App\Models\TipoComprobanteFe;

/**
 * TipoComprobanteFePolicy - Gestión de autorización para TipoComprobanteFe
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoComprobanteFePolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     * 
     * @var string
     */
    protected string $permission = 'tipo_comprobante_fe';
}
