<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth('sanctum')->check()) {
                $builder->where('empresa_id', auth('sanctum')->user()->empresa_id);
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa');
    }
}