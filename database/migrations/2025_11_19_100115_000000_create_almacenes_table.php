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
        Schema::create('almacenes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('sucursal_id')->nullable();
            $table->string('nombre', 255);
            $table->string('codigo', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('ubicacion')->nullable();
            $table->unsignedInteger('responsable_id')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('set null');
            $table->foreign('responsable_id')->references('id')->on('empleados')->onDelete('set null');

            $table->unique(['empresa_id', 'codigo'], 'unique_empresa_codigo_almacen');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('responsable_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index('es_principal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('almacenes');
    }
};
