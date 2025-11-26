<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fe_lineas_detalle', function (Blueprint $table) {
            $table->id();
            
            // Relación con el comprobante
            $table->unsignedBigInteger('comprobante_id');
            $table->foreign('comprobante_id')
                ->references('id')
                ->on('comprobantes_electronicos_fe')
                ->onDelete('cascade');
            
            // Número de línea en el comprobante
            $table->integer('numero_linea')->comment('Orden de la línea en el comprobante');
            
            // Código y descripción del producto/servicio
            $table->string('codigo_tipo', 2)->default('04')->comment('01=Interno, 02=Comprador, 03=Vendedor, 04=CABYS');
            $table->string('codigo', 20)->comment('Código del producto/servicio');
            $table->string('codigo_comercial', 50)->nullable()->comment('Código comercial del vendedor/comprador');
            $table->text('detalle')->comment('Descripción del producto o servicio');
            
            // Cantidad y unidad de medida
            $table->decimal('cantidad', 18, 5)->comment('Cantidad del producto/servicio');
            $table->string('unidad_medida', 10)->default('Unid')->comment('Unidad de medida');
            $table->string('unidad_medida_comercial', 50)->nullable()->comment('Unidad de medida comercial');
            
            // Precios
            $table->decimal('precio_unitario', 18, 5)->comment('Precio unitario antes de impuestos');
            $table->decimal('monto_total', 18, 5)->comment('Subtotal de la línea (cantidad * precio)');
            $table->decimal('monto_descuento', 18, 5)->default(0)->comment('Descuento aplicado');
            $table->text('naturaleza_descuento')->nullable()->comment('Razón del descuento');
            $table->decimal('subtotal', 18, 5)->comment('Subtotal después del descuento');
            
            // Base imponible e impuestos
            $table->decimal('base_imponible', 18, 5)->default(0)->comment('Base sobre la que se calcula el impuesto');
            
            // Impuestos de la línea (IVA)
            $table->string('impuesto_codigo', 2)->nullable()->comment('01=IVA, 02=Selectivo, etc.');
            $table->string('impuesto_codigo_tarifa', 2)->nullable()->comment('01=Tarifa 0%, 02=Reducida, 08=Tarifa general 13%');
            $table->decimal('impuesto_tarifa', 5, 2)->default(0)->comment('Porcentaje de impuesto (ej: 13.00)');
            $table->decimal('impuesto_monto', 18, 5)->default(0)->comment('Monto del impuesto');
            
            // Exoneraciones (si aplican)
            $table->string('exoneracion_tipo_documento', 2)->nullable()->comment('01=Compra, 02=Venta, etc.');
            $table->string('exoneracion_numero_documento', 50)->nullable()->comment('Número del documento de exoneración');
            $table->string('exoneracion_nombre_institucion', 200)->nullable()->comment('Institución que emite exoneración');
            $table->date('exoneracion_fecha_emision')->nullable()->comment('Fecha emisión documento exoneración');
            $table->decimal('exoneracion_porcentaje', 5, 2)->nullable()->comment('Porcentaje exonerado');
            $table->decimal('exoneracion_monto', 18, 5)->default(0)->comment('Monto exonerado');
            
            // Total de la línea
            $table->decimal('monto_total_linea', 18, 5)->comment('Total de la línea con impuestos');
            
            // Metadatos adicionales
            $table->json('metadata')->nullable()->comment('Información adicional en JSON');
            
            $table->timestamps();
            
            // Índices
            $table->index(['comprobante_id', 'numero_linea']);
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_lineas_detalle');
    }
};
