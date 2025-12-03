<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para agregar campo ProveedorSistemas a empresas
 *
 * Requerido para facturación electrónica v4.4 según DGT-R-000-2024.
 * Este campo es OBLIGATORIO en todos los comprobantes electrónicos v4.4.
 *
 * @see DGT-R-000-2024 Estructura del Comprobante Electrónico
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Campo para identificar el proveedor del sistema de facturación electrónica
            // Obligatorio en v4.4, se incluye en cada comprobante emitido
            $table->string('proveedor_sistemas', 100)
                ->nullable()
                ->after('actividad_economica_principal')
                ->comment('Identificación del proveedor del sistema de facturación electrónica (v4.4)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('proveedor_sistemas');
        });
    }
};
