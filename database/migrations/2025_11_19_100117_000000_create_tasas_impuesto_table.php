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
        Schema::create('tasas_impuesto', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tipo_impuesto_id');
            $table->decimal('tasa_porcentaje', 5, 2);
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('tipo_impuesto_id')->references('id')->on('tipos_impuesto')->onDelete('cascade');

            $table->index('tipo_impuesto_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index(['fecha_inicio_vigencia', 'fecha_fin_vigencia']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasas_impuesto');
    }
};
