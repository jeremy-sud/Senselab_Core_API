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
        'tipo_documento_dgt',
        'prefijo',
        'consecutivo_actual',
        'estado',
        'fecha_autorizacion'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'consecutivo_actual' => 'integer',
        'fecha_autorizacion' => 'date',
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
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por tipo de documento.
     */
    public function scopePorTipoDocumento($query, $tipo)
    {
        return $query->where('tipo_documento_dgt', $tipo);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Obtiene el siguiente consecutivo y lo incrementa.
     */
    public function obtenerSiguienteConsecutivo(): ?string
    {
        if ($this->estado !== self::ESTADO_ACTIVO) {
            throw new \Exception('El consecutivo no está activo.');
        }

        $siguiente = str_pad($this->consecutivo_actual, 10, '0', STR_PAD_LEFT);
        $this->increment('consecutivo_actual');

        // Si se alcanza el máximo permitido, marcar como agotado
        if ($this->consecutivo_actual >= 9999999999) {
            $this->update(['estado' => self::ESTADO_AGOTADO]);
        }

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
            // Validar tipo de documento
            if (!in_array($consecutivo->tipo_documento_dgt, [
                self::TIPO_FACTURA_ELECTRONICA,
                self::TIPO_NOTA_DEBITO,
                self::TIPO_NOTA_CREDITO,
                self::TIPO_TIQUETE_ELECTRONICO,
                self::TIPO_FACTURA_COMPRA,
                self::TIPO_FACTURA_EXPORTACION
            ])) {
                throw new \Exception('Tipo de documento DGT no válido.');
            }

            // Validar estado
            if (!in_array($consecutivo->estado, [
                self::ESTADO_ACTIVO,
                self::ESTADO_AGOTADO,
                self::ESTADO_INACTIVO
            ])) {
                throw new \Exception('Estado no válido.');
            }

            // Validar prefijo (3 dígitos)
            if (!preg_match('/^\d{3}$/', $consecutivo->prefijo)) {
                throw new \Exception('El prefijo debe ser un número de 3 dígitos.');
            }

            // Validar unicidad de la combinación empresa, tipo y prefijo
            $exists = static::where('id', '!=', $consecutivo->id)
                ->where('empresa_id', $consecutivo->empresa_id)
                ->where('tipo_documento_dgt', $consecutivo->tipo_documento_dgt)
                ->where('prefijo', $consecutivo->prefijo)
                ->exists();

            if ($exists) {
                throw new \Exception('Ya existe un consecutivo con esta combinación de empresa, tipo y prefijo.');
            }
        });
    }

    /**
     * Obtiene la descripción del tipo de documento.
     */
    public function getTipoDocumentoDescripcionAttribute(): string
    {
        return match($this->tipo_documento_dgt) {
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