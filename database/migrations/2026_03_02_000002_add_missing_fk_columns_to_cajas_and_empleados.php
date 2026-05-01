<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Agregar columnas FK faltantes a tablas existentes.
 *
 * Columnas referenciadas activamente en controllers pero ausentes en migraciones:
 * - cajas.usuario_id → CajaController eager loads 'usuario'
 * - empleados.usuario_id → EmpleadoController eager loads 'usuario'
 * - empleados.departamento_id → EmpleadoController eager loads 'departamento'
 */
return new class extends Migration
{
    public function up(): void
    {
        // Agregar usuario_id a cajas (relación con el usuario asignado a la caja)
        Schema::table('cajas', function (Blueprint $table) {
            $table->unsignedInteger('usuario_id')
                  ->nullable()
                  ->after('sucursal_id');
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('usuarios')
                  ->nullOnDelete();
        });

        // Agregar usuario_id y departamento_id a empleados
        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedInteger('usuario_id')
                  ->nullable()
                  ->after('empresa_id');
            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('usuarios')
                  ->nullOnDelete();

            $table->unsignedBigInteger('departamento_id')
                  ->nullable()
                  ->after('cargo_id');
            $table->foreign('departamento_id')
                  ->references('id')
                  ->on('departamentos')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropForeign(['departamento_id']);
            $table->dropColumn('departamento_id');
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropColumn('usuario_id');
        });
    }
};
