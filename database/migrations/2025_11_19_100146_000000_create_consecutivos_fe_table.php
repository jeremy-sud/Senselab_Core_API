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
        Schema::create('consecutivos_fe', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('sucursal_id');
            $table->string('tipo_comprobante', 50);
            $table->string('prefijo', 20)->nullable();
            $table->integer('consecutivo_actual')->default(0);
            $table->integer('consecutivo_inicial')->default(1);
            $table->integer('consecutivo_final')->nullable();
            $table->date('fecha_resolucion')->nullable();
            $table->string('numero_resolucion', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('restrict');

            $table->unique(['empresa_id', 'sucursal_id', 'tipo_comprobante'], 'unique_consecutivo');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('tipo_comprobante');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consecutivos_fe');
    }
};
