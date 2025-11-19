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
        Schema::create('rutas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 255);
            $table->string('origen', 255);
            $table->string('destino', 255);
            $table->decimal('distancia_km', 10, 2)->nullable();
            $table->integer('duracion_estimada')->nullable()->comment('En minutos');
            $table->decimal('tarifa_base', 10, 2);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

            $table->index('empresa_id');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};
