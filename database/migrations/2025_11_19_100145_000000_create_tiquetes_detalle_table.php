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
        Schema::create('tiquetes_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('detalle_venta_id');
            $table->unsignedInteger('horario_ruta_id');
            $table->string('asiento_numero', 10);
            $table->string('nombre_pasajero', 255)->nullable();
            $table->string('identificacion_pasajero', 50)->nullable();
            $table->decimal('precio_final_tiquete', 10, 2);
            $table->string('estado', 50)->comment('Vendido, Usado, Cancelado');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('detalle_venta_id')->references('id')->on('detalle_ventas')->onDelete('cascade');
            $table->foreign('horario_ruta_id')->references('id')->on('horarios_ruta')->onDelete('restrict');

            $table->unique(['horario_ruta_id', 'asiento_numero'], 'unique_horario_asiento');
            $table->index('detalle_venta_id');
            $table->index('horario_ruta_id');
            $table->index('estado');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiquetes_detalle');
    }
};
