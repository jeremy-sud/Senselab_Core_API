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
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venta_id');
            $table->unsignedInteger('producto_id');
            $table->integer('numero_linea')->default(1);
            $table->decimal('cantidad', 15, 2);
            $table->decimal('precio_unitario', 15, 2);
            $table->decimal('subtotal_linea', 15, 2);
            $table->decimal('porcentaje_descuento', 5, 2)->default(0.00);
            $table->decimal('monto_descuento', 15, 2)->default(0.00);
            $table->decimal('subtotal_con_descuento', 15, 2);
            $table->unsignedInteger('tipo_impuesto_id')->nullable();
            $table->decimal('tasa_impuesto', 5, 2)->default(0.00);
            $table->decimal('monto_impuesto', 15, 2)->default(0.00);
            $table->decimal('total_linea', 15, 2);
            $table->text('detalle_adicional')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');
            $table->foreign('tipo_impuesto_id')->references('id')->on('tipos_impuesto')->onDelete('set null');

            $table->index('venta_id');
            $table->index('producto_id');
            $table->index('tipo_impuesto_id');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
