<?php

namespace App\Policies;

use App\Models\ComprobanteRecibidoElectronico;

/**
 * ComprobanteRecibidoElectronicoPolicy - Gestión de autorización para ComprobanteRecibidoElectronico
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ComprobanteRecibidoElectronicoPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'comprobante_recibido_electronico';
}
