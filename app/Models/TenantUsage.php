<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUsage extends Model
{
    protected $table = 'tenant_usages';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'tenant_id',
        'active_users_count',
        'invoices_count_current_month',
        'ai_queries_count_current_month',
    ];

    protected $casts = [
        'active_users_count' => 'integer',
        'invoices_count_current_month' => 'integer',
        'ai_queries_count_current_month' => 'integer',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];
}
