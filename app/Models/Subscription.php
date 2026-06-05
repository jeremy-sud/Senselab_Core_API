<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $table = 'subscriptions';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'tenant_id',
        'empresa_id',
        'usuario_id',
        'plan',
        'status',
        'max_users',
        'max_invoices_month',
        'max_ai_queries_month',
        'current_period_end',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_price_id',
    ];

    protected $casts = [
        'max_users' => 'integer',
        'max_invoices_month' => 'integer',
        'max_ai_queries_month' => 'integer',
        'current_period_end' => 'datetime',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Relación con la empresa
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Relación con el usuario administrador titular
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
