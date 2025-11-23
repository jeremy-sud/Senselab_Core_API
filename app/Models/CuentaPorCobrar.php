<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class CuentaPorCobrar extends Model
{
    use BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'cuentas_por_cobrar';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cliente_id',
        'venta_id',
        'numero_documento',
        'fecha_emision',
        'fecha_vencimiento',
        'moneda',
        'monto_original',
        'monto_pagado',
        'estado',
        'observaciones',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_original' => 'decimal:5',
        'monto_pagado' => 'decimal:5',
        'monto_pendiente' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Los atributos que deben ser ocultados para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'eliminado',
    ];

    /**
     * Los atributos computados que deben ser agregados a los arrays.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'esta_vencida',
    ];

    /**
     * Las reglas de validación para el modelo.
     *
     * @var array<string, string>
     */
    public static $rules = [
        'cliente_id' => 'nullable|exists:clientes,id',
        'venta_id' => 'nullable|exists:ventas,id',
        'numero_documento' => 'nullable|string|max:100',
        'fecha_emision' => 'required|date',
        'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision',
        'moneda' => 'required|string|size:3',
        'monto_original' => 'required|numeric|min:0',
        'monto_pagado' => 'required|numeric|min:0',
        'estado' => 'required|string|max:50|in:Pendiente,Pagada Parcialmente,Pagada Totalmente,Vencida,Anulada',
        'observaciones' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the empresa that owns the cuenta por cobrar.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Get the cliente that owns the cuenta por cobrar.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Get the venta associated with the cuenta por cobrar.
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Get the pagos for the cuenta por cobrar.
     */
    public function pagos()
    {
        return $this->hasMany(PagoCuentaCobrar::class);
    }

    /**
     * Determina si la cuenta por cobrar está vencida.
     *
     * @return bool
     */
    public function getEstaVencidaAttribute()
    {
        return $this->fecha_vencimiento < now() && 
               $this->monto_pendiente > 0 && 
               $this->estado !== 'Anulada';
    }

    /**
     * Scope para filtrar cuentas por cobrar activas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar cuentas por cobrar pendientes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente')
                    ->orWhere('estado', 'Pagada Parcialmente');
    }

    /**
     * Scope para filtrar cuentas por cobrar vencidas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVencidas($query)
    {
        return $query->where('fecha_vencimiento', '<', now())
                    ->whereRaw('monto_original - monto_pagado > 0')
                    ->where('estado', '!=', 'Anulada');
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Actualizar estado basado en los montos
            if ($model->monto_pagado >= $model->monto_original) {
                $model->estado = 'Pagada Totalmente';
            } elseif ($model->monto_pagado > 0) {
                $model->estado = 'Pagada Parcialmente';
            } elseif ($model->fecha_vencimiento < now()) {
                $model->estado = 'Vencida';
            }
        });
    }
}