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
        Schema::create('horarios_ruta', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ruta_id');
            $table->unsignedInteger('bus_id')->nullable();
            $table->date('fecha_salida')->comment('1=Domingo, 7=Sábado.');
            $table->time('hora_salida');
            $table->date('fecha_llegada_estimada')->nullable();
            $table->time('hora_llegada_estimada')->nullable();
            $table->integer('asientos_disponibles')->nullable()->comment('Calculado: capacidad del bus - tiquetes vendidos');
            $table->string('estado', 50)->comment('Programado, Cancelado, En Viaje, Finalizado');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Índices y Foreign Keys
            $table->index('ruta_id', 'horarios_ruta_ibfk_1');
            $table->index('bus_id', 'horarios_ruta_ibfk_2');
            
            $table->foreign('ruta_id', 'horarios_ruta_ibfk_1')
                  ->references('id')->on('rutas')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->foreign('bus_id', 'horarios_ruta_ibfk_2')
                  ->references('id')->on('buses_unidades')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_ruta');
    }
};
