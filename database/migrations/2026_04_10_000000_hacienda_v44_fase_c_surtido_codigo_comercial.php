<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase C — DetalleSurtido y CodigoComercial
 *
 * Implementa las 2 brechas pendientes del análisis comparativo Hacienda v4.4:
 *
 * - Brecha #31: DetalleSurtido — Estructura completa para surtidos/combos
 *   Tablas: fe_detalle_surtido, fe_surtido_impuesto
 *
 * - Brecha #33: CodigoComercial {0,5} — Estructura normalizada para códigos comerciales
 *   Tabla: fe_codigo_comercial
 *
 * @see docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // BRECHA #33: CodigoComercial {0,5} — Códigos comerciales por línea
        // =====================================================
        // Spec v4.4: Hasta 5 repeticiones de CodigoComercial por LineaDetalle
        // Cada uno con Tipo (01-05, 99) y Codigo (string 20)
        if (!Schema::hasTable('fe_codigo_comercial')) {
            Schema::create('fe_codigo_comercial', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('linea_detalle_id');
                $table->tinyInteger('orden')->default(1)
                    ->comment('Orden del código (1-5)');
                $table->string('tipo', 2)
                    ->comment('Tipo de código: 01=Interno, 02=Proveedor, 03=EAN/UPC, 04=Estándar industria, 99=Otro');
                $table->string('codigo', 20)
                    ->comment('Valor del código comercial');
                $table->timestamps();

                $table->foreign('linea_detalle_id')
                    ->references('id')->on('fe_lineas_detalle')
                    ->onDelete('cascade');
                $table->index('linea_detalle_id');
                $table->unique(['linea_detalle_id', 'orden'], 'fe_codigo_comercial_linea_orden_unique');
            });
        }

        // =====================================================
        // BRECHA #31: DetalleSurtido — Líneas de surtido/combo
        // =====================================================
        // Spec v4.4: Hasta 20 LineaDetalleSurtido por LineaDetalle
        // Obligatorio cuando se facturan surtidos con diferentes tarifas IVA
        if (!Schema::hasTable('fe_detalle_surtido')) {
            Schema::create('fe_detalle_surtido', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('linea_detalle_id');
                $table->tinyInteger('numero_linea_surtido')
                    ->comment('Número de línea dentro del surtido (1-20)');
                $table->string('codigo_cabys_surtido', 13)
                    ->comment('Código CAByS del ítem del surtido');
                $table->decimal('cantidad_surtido', 16, 3)
                    ->comment('Cantidad del ítem en el surtido');
                $table->string('unidad_medida_surtido', 15)->default('Unid')
                    ->comment('Unidad de medida del ítem');
                $table->string('detalle_surtido', 200)
                    ->comment('Descripción del ítem del surtido');
                $table->decimal('precio_unitario_surtido', 18, 5)
                    ->comment('Precio unitario del ítem');
                $table->decimal('monto_total_surtido', 18, 5)
                    ->comment('Monto total = cantidad × precio');
                $table->decimal('monto_descuento_surtido', 18, 5)->default(0)
                    ->comment('Descuento aplicado al ítem');
                $table->decimal('subtotal_surtido', 18, 5)
                    ->comment('Subtotal = monto_total - descuento');
                $table->timestamps();

                $table->foreign('linea_detalle_id')
                    ->references('id')->on('fe_lineas_detalle')
                    ->onDelete('cascade');
                $table->index('linea_detalle_id');
            });
        }

        // =====================================================
        // BRECHA #31: ImpuestoSurtido {1,1000} — Impuestos por línea de surtido
        // =====================================================
        if (!Schema::hasTable('fe_surtido_impuesto')) {
            Schema::create('fe_surtido_impuesto', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('detalle_surtido_id');
                $table->string('codigo', 2)
                    ->comment('Código de impuesto: 01=IVA, 02=Selectivo, etc.');
                $table->string('codigo_tarifa_iva', 2)->nullable()
                    ->comment('Código tarifa IVA (nota 8.1)');
                $table->decimal('tarifa', 4, 2)->nullable()
                    ->comment('Porcentaje de tarifa');
                $table->decimal('monto', 18, 5)->default(0)
                    ->comment('Monto del impuesto');
                $table->decimal('monto_exportacion', 18, 5)->nullable()
                    ->comment('Monto para exportación');
                $table->timestamps();

                $table->foreign('detalle_surtido_id')
                    ->references('id')->on('fe_detalle_surtido')
                    ->onDelete('cascade');
                $table->index('detalle_surtido_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fe_surtido_impuesto');
        Schema::dropIfExists('fe_detalle_surtido');
        Schema::dropIfExists('fe_codigo_comercial');
    }
};
