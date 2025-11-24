<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsecutivoFe extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'consecutivos_fe';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Estados del consecutivo.
     */
    const ESTADO_ACTIVO = 'Activo';
    const ESTADO_AGOTADO = 'Agotado';
    const ESTADO_INACTIVO = 'Inactivo';

    /**
     * Tipos de documentos según DGT.
     */
    const TIPO_FACTURA_ELECTRONICA = '01';
    const TIPO_NOTA_DEBITO = '02';
    const TIPO_NOTA_CREDITO = '03';
    const TIPO_TIQUETE_ELECTRONICO = '04';
    const TIPO_FACTURA_COMPRA = '08';
    const TIPO_FACTURA_EXPORTACION = '09';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'tipo_comprobante',
        'prefijo',
        'consecutivo_actual',
        'consecutivo_inicial',
        'consecutivo_final',
        'fecha_resolucion',
        'numero_resolucion',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'consecutivo_actual' => 'integer',
        'consecutivo_inicial' => 'integer',
        'consecutivo_final' => 'integer',
        'fecha_resolucion' => 'date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Relación con la empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con la sucursal.
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Scope para obtener consecutivos activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por tipo de comprobante.
     */
    public function scopePorTipoComprobante($query, $tipo)
    {
        return $query->where('tipo_comprobante', $tipo);
    }

    /**
     * Obtiene el siguiente consecutivo y lo incrementa.
     */
    public function obtenerSiguienteConsecutivo(): ?string
    {
        if (!$this->activo) {
            throw new \Exception('El consecutivo no está activo.');
        }

        // Verificar si se alcanzó el límite
        if ($this->consecutivo_final && $this->consecutivo_actual >= $this->consecutivo_final) {
            throw new \Exception('Se alcanzó el consecutivo final autorizado.');
        }

        $siguiente = str_pad($this->consecutivo_actual, 10, '0', STR_PAD_LEFT);
        $this->increment('consecutivo_actual');

        return $siguiente;
    }

    /**
     * Formatea el consecutivo completo según formato DGT.
     */
    public function formatearConsecutivoCompleto(string $consecutivo): string
    {
        return $this->prefijo . $consecutivo;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($consecutivo) {
            // Validar consecutivos
            if ($consecutivo->consecutivo_actual < $consecutivo->consecutivo_inicial) {
                $consecutivo->consecutivo_actual = $consecutivo->consecutivo_inicial;
            }

            if ($consecutivo->consecutivo_final && $consecutivo->consecutivo_actual > $consecutivo->consecutivo_final) {
                throw new \Exception('El consecutivo actual no puede ser mayor al consecutivo final.');
            }

            // Validar unicidad de la combinación empresa, sucursal y tipo
            $exists = static::where('id', '!=', $consecutivo->id)
                ->where('empresa_id', $consecutivo->empresa_id)
                ->where('sucursal_id', $consecutivo->sucursal_id)
                ->where('tipo_comprobante', $consecutivo->tipo_comprobante)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe un consecutivo para esta combinación de empresa, sucursal y tipo de comprobante.');
            }
        });
    }

    /**
     * Obtiene la descripción del tipo de comprobante.
     */
    public function getTipoComprobanteDescripcionAttribute(): string
    {
        return match($this->tipo_comprobante) {
            self::TIPO_FACTURA_ELECTRONICA => 'Factura Electrónica',
            self::TIPO_NOTA_DEBITO => 'Nota de Débito',
            self::TIPO_NOTA_CREDITO => 'Nota de Crédito',
            self::TIPO_TIQUETE_ELECTRONICO => 'Tiquete Electrónico',
            self::TIPO_FACTURA_COMPRA => 'Factura de Compra',
            self::TIPO_FACTURA_EXPORTACION => 'Factura de Exportación',
            default => 'Desconocido'
        };
    }
}