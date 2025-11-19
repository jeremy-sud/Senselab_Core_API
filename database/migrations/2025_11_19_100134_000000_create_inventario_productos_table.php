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
        Schema::create('inventario_productos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('almacen_id');
            $table->unsignedInteger('producto_id');
            $table->decimal('stock_actual', 15, 2)->default(0.00);
            $table->decimal('costo_promedio', 15, 2)->default(0.00);
            $table->decimal('stock_minimo', 15, 2)->default(0.00);
            $table->decimal('stock_maximo', 15, 2)->default(0.00);
            $table->string('ubicacion', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

            $table->unique(['almacen_id', 'producto_id'], 'unique_almacen_producto');
            $table->index('almacen_id');
            $table->index('producto_id');
            $table->index('stock_actual');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario_productos');
    }
};
