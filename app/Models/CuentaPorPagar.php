<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class CuentaPorPagar extends Model
{
    use BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'cuentas_por_pagar';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proveedor_id',
        'orden_compra_id',
        'comprobante_recibido_id',
        'documento_referencia_proveedor',
        'fecha_emision_documento',
        'fecha_recepcion_documento',
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
        'fecha_emision_documento' => 'date',
        'fecha_recepcion_documento' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_original' => 'decimal:5',
        'monto_pagado' => 'decimal:5',
        'saldo_pendiente' => 'decimal:2',
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
        'proveedor_id' => 'required|exists:proveedores,id',
        'orden_compra_id' => 'nullable|exists:ordenes_compra,id',
        'comprobante_recibido_id' => 'nullable|exists:comprobantes_recibidos_electronicos,id',
        'documento_referencia_proveedor' => 'required|string|max:100',
        'fecha_emision_documento' => 'required|date',
        'fecha_recepcion_documento' => 'nullable|date',
        'fecha_vencimiento' => 'required|date|after_or_equal:fecha_emision_documento',
        'moneda' => 'required|string|size:3',
        'monto_original' => 'required|numeric|min:0',
        'monto_pagado' => 'required|numeric|min:0',
        'estado' => 'required|string|max:50|in:Pendiente,Pagada Parcialmente,Pagada Totalmente,Vencida,Anulada',
        'observaciones' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the empresa that owns the cuenta por pagar.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Get the proveedor that owns the cuenta por pagar.
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Get the orden de compra associated with the cuenta por pagar.
     */
    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    /**
     * Get the comprobante recibido associated with the cuenta por pagar.
     */
    public function comprobanteRecibido()
    {
        return $this->belongsTo(ComprobanteRecibidoElectronico::class, 'comprobante_recibido_id');
    }

    /**
     * Get the pagos for the cuenta por pagar.
     */
    public function pagos()
    {
        return $this->hasMany(PagoCuentaPorPagar::class);
    }

    /**
     * Determina si la cuenta por pagar está vencida.
     *
     * @return bool
     */
    public function getEstaVencidaAttribute()
    {
        return $this->fecha_vencimiento < now() && 
               $this->saldo_pendiente > 0 && 
               $this->estado !== 'Anulada';
    }

    /**
     * Scope para filtrar cuentas por pagar activas.
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
     * Scope para filtrar cuentas por pagar pendientes.
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
     * Scope para filtrar cuentas por pagar vencidas.
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