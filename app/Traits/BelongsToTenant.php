<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        // Global scope para filtrar por empresa del usuario autenticado o tenant actual
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = static::resolveTenantId();

            if ($tenantId !== null) {
                $builder->where('empresa_id', $tenantId);
            }
        });

        // Asignar automáticamente empresa_id al crear un nuevo modelo
        static::creating(function ($model) {
            if (empty($model->empresa_id)) {
                $tenantId = static::resolveTenantId();

                if ($tenantId !== null) {
                    $model->empresa_id = $tenantId;
                }
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa');
    }

    protected static function resolveTenantId(): ?int
    {
        if (auth('sanctum')->check()) {
            return auth('sanctum')->user()->empresa_id;
        }

        $containerKey = config('multitenancy.current_tenant_container_key');

        if ($containerKey && app()->has($containerKey)) {
            return (int) app($containerKey)->getKey();
        }

        return null;
    }
}