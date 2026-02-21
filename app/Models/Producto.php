<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\ProductoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `productos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Producto extends Model
{
    /** @use HasFactory<\Database\Factories\ProductoFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'productos';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'unidad_medida_id',
        'marca_id',
        'proveedor_id',
        'tipo_impuesto_id',
        'cabys_id',
        'codigo',
        'codigo_barras',
        'nombre',
        'descripcion',
        'precio_venta',
        'precio_compra',
        'stock_minimo',
        'stock_maximo',
        'tipo_producto',
        'vende',
        'compra',
        'controla_inventario',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'precio_compra' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'vende' => 'boolean',
        'compra' => 'boolean',
        'controla_inventario' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string',
        'precio_venta' => 'required|numeric|min:0',
        'precio_compra' => 'required|numeric|min:0',
        'tipo_producto' => 'required|string',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function categoria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function unidadMedida(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }

    public function marca(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function proveedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function tipoImpuesto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TipoImpuesto::class, 'tipo_impuesto_id');
    }

    public function cabys(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cabys::class, 'cabys_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa(mixed $q, mixed $empresaId): Builder{
        return $q->where('empresa_id', $empresaId);
    }

    public function scopePorCategoria(mixed $q, mixed $categoriaId): Builder{
        return $q->where('categoria_id', $categoriaId);
    }

    public function scopePorTipo(mixed $q, mixed $tipo): Builder{
        return $q->where('tipo_producto', $tipo);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Producto $p) {
            if (isset($p->nombre)) {
                $p->nombre = trim($p->nombre);
            }
            if (isset($p->codigo)) {
                $p->codigo = Str::upper(trim($p->codigo));
            }
            if (isset($p->codigo_barras)) {
                $p->codigo_barras = trim($p->codigo_barras);
            }
        });
    }
}
