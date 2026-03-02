<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Crear tabla departamentos.
 *
 * Necesaria para la relación Empleado->departamento() usada en EmpleadoController.
 * Los departamentos son catálogos globales compartidos entre empresas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('descripcion', 500)->nullable();
            $table->string('codigo', 20)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
