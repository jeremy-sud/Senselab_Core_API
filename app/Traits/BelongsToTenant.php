<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Global scope para filtrar por empresa del usuario autenticado
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth('sanctum')->check()) {
                $builder->where('empresa_id', auth('sanctum')->user()->empresa_id);
            }
        });

        // Asignar automáticamente empresa_id al crear un nuevo modelo
        static::creating(function ($model) {
            if (auth('sanctum')->check() && empty($model->empresa_id)) {
                $model->empresa_id = auth('sanctum')->user()->empresa_id;
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa');
    }
}