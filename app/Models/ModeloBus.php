<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloBus extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'modelos_buses';

    /**
     * Indica si el modelo debe ser timestampeable.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'nombre'
    ];

    /**
     * Relación con las unidades de este modelo (Alias para 'unidades' usado en controlador).
     */
    public function busesUnidades(): HasMany
    {
        return $this->hasMany(BusUnidad::class, 'modelo_id');
    }

    /**
     * Scope para buscar por nombre.
     */
    public function scopePorNombre($query, $nombre)
    {
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }
}