<?php

namespace App\Models\Traits;

trait CustomTimestamps
{
    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    public function getCreatedAtColumn()
    {
        return 'creado_en';
    }

    public function getUpdatedAtColumn()
    {
        return 'actualizado_en';
    }

    /**
     * Inicializar el trait.
     */
    protected function initializeCustomTimestamps()
    {
        if (!defined('static::CREATED_AT')) {
            define('static::CREATED_AT', 'creado_en');
        }
        if (!defined('static::UPDATED_AT')) {
            define('static::UPDATED_AT', 'actualizado_en');
        }
    }
}
