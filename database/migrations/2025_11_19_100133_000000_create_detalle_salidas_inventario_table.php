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
        Schema::create('detalle_salidas_inventario', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('salida_inventario_id');
            $table->unsignedInteger('producto_id');
            $table->integer('numero_linea')->default(1);
            $table->decimal('cantidad', 15, 2);
            $table->decimal('costo_unitario', 15, 2);
            $table->decimal('total_linea', 15, 2);
            $table->string('lote', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('salida_inventario_id')->references('id')->on('salidas_inventario')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('restrict');

            $table->index('salida_inventario_id');
            $table->index('producto_id');
            $table->index('lote');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_salidas_inventario');
    }
};
