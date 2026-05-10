<?php

namespace App\Policies;

use App\Models\UrlShortener;

/**
 * UrlShortenerPolicy - Gestión de autorización para UrlShortener
 *
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class UrlShortenerPolicy extends BasePolicy
{
    /**
     * Prefijo del permiso
     *
     * @var string
     */
    protected string $permission = 'url_shortener';
}
