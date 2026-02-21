<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
/** @use HasFactory<\Database\Factories\ComprobanteElectronicoFeFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

/**
 * Modelo para Comprobantes Electrónicos de Facturación Electrónica Costa Rica
 * 
 * Representa facturas, notas de crédito/débito y tiquetes electrónicos
 * según estándares del Ministerio de Hacienda de Costa Rica.
 */
class ComprobanteElectronicoFe extends Model
{
    /** @use HasFactory<\Database\Factories\ComprobanteElectronicoFeFactory> */
    use HasFactory, SoftDeletes, BelongsToTenant;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'comprobantes_electronicos_fe';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'empresa_id',
        'tipo_documento',
        'clave',
        'consecutivo',
        'fecha_emision',
        'receptor_tipo_identificacion',
        'receptor_numero_identificacion',
        'receptor_nombre',
        'receptor_email',
        'moneda',
        'tipo_cambio',
        'total_servicios_gravados',
        'total_servicios_exentos',
        'total_servicios_exonerados',
        'total_mercancias_gravadas',
        'total_mercancias_exentas',
        'total_mercancias_exoneradas',
        'total_gravado',
        'total_exento',
        'total_exonerado',
        'total_venta',
        'total_descuentos',
        'total_venta_neta',
        'total_impuesto',
        'total_iva_devuelto',
        'total_otros_cargos',
        'total_comprobante',
        'condicion_venta',
        'medio_pago',
        'plazo_credito',
        'xml_original',
        'xml_firmado',
        'estado',
        'situacion',
        'respuesta_hacienda_xml',
        'mensaje_hacienda',
        'codigo_respuesta_hacienda',
        'fecha_envio',
        'fecha_recibido',
        'fecha_procesado',
        'fecha_respuesta',
        'intentos_envio',
        'ultimo_intento',
        'ultimo_error',
        'metadata',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'fecha_emision' => 'datetime',
        'tipo_cambio' => 'decimal:5',
        'total_servicios_gravados' => 'decimal:5',
        'total_servicios_exentos' => 'decimal:5',
        'total_servicios_exonerados' => 'decimal:5',
        'total_mercancias_gravadas' => 'decimal:5',
        'total_mercancias_exentas' => 'decimal:5',
        'total_mercancias_exoneradas' => 'decimal:5',
        'total_gravado' => 'decimal:5',
        'total_exento' => 'decimal:5',
        'total_exonerado' => 'decimal:5',
        'total_venta' => 'decimal:5',
        'total_descuentos' => 'decimal:5',
        'total_venta_neta' => 'decimal:5',
        'total_impuesto' => 'decimal:5',
        'total_iva_devuelto' => 'decimal:5',
        'total_otros_cargos' => 'decimal:5',
        'total_comprobante' => 'decimal:5',
        'plazo_credito' => 'integer',
        'fecha_envio' => 'datetime',
        'fecha_recibido' => 'datetime',
        'fecha_procesado' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'intentos_envio' => 'integer',
        'ultimo_intento' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una Empresa.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación: Tiene muchas líneas de detalle.
     */
    public function lineasDetalle(): HasMany
    {
        return $this->hasMany(FeLineaDetalle::class, 'comprobante_id');
    }

    /**
     * Alias para lineasDetalle (compatibilidad).
     */
    public function lineas(): HasMany
    {
        return $this->lineasDetalle();
    }

    /**
     * Scope: Filtrar por estado.
     */
    public function scopeEstado(Builder $query, string $estado): Builder{
        return $query->where('estado', $estado);
    }

    /**
     * Scope: Comprobantes pendientes de envío.
     */
    public function scopePendientes(Builder $query): Builder{
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope: Comprobantes aceptados por Hacienda.
     */
    public function scopeAceptados(Builder $query): Builder{
        return $query->where('estado', 'aceptado');
    }

    /**
     * Scope: Comprobantes rechazados por Hacienda.
     */
    public function scopeRechazados(Builder $query): Builder{
        return $query->where('estado', 'rechazado');
    }

    /**
     * Scope: Filtrar por tipo de documento.
     */
    public function scopeTipoDocumento(Builder $query, string $tipo): Builder{
        return $query->where('tipo_documento', $tipo);
    }

    /**
     * Scope: Filtrar por rango de fechas.
     */
    public function scopeFechaEmisionEntre(Builder $query, mixed $fechaInicio, mixed $fechaFin): Builder{
        return $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
    }

    /**
     * Accessor: Obtener el nombre del tipo de documento.
     */
    public function getTipoDocumentoNombreAttribute(): string
    {
        $tipos = config('hacienda.tipos_comprobante');
        return $tipos[$this->tipo_documento] ?? 'Desconocido';
    }

    /**
     * Accessor: Verificar si el comprobante fue aceptado.
     */
    public function getAceptadoAttribute(): bool
    {
        return $this->estado === 'aceptado';
    }

    /**
     * Accessor: Verificar si el comprobante fue rechazado.
     */
    public function getRechazadoAttribute(): bool
    {
        return $this->estado === 'rechazado';
    }

    /**
     * Accessor: Verificar si el comprobante está pendiente.
     */
    public function getPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }
}
