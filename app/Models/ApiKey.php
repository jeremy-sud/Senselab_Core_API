<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_keys';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empresa_id',
        'name',
        'prefix',
        'token_hash',
        'environment',
        'activo',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Relación con la empresa/inquilino propietaria de esta llave de API.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
