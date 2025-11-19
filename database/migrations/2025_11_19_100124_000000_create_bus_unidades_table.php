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
        Schema::create('buses_unidades', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('placa', 20)->comment('Placa o matrícula del vehículo');
            $table->unsignedInteger('modelo_id')->nullable();
            $table->integer('capacidad_asientos');
            $table->string('identificador_interno', 50)->nullable()->comment('Ej: Número de flota 101');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Índices y Foreign Keys
            $table->unique(['empresa_id', 'placa'], 'buses_unidades_ibfk_1');
            $table->index('modelo_id', 'fk_bus_modelo');
            
            $table->foreign('empresa_id', 'buses_unidades_ibfk_1')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            $table->foreign('modelo_id', 'fk_bus_modelo')
                  ->references('id')->on('modelos_buses')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses_unidades');
    }
};
