<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para agregar campos v4.4 a tablas de facturación electrónica
 *
 * Cambios según DGT-R-000-2024:
 * - BaseImponible: Obligatorio cuando hay impuesto
 * - ImpuestoNeto: Impuesto neto de la línea
 * - CodigoCABYS: Código CABYS del producto (opcional pero recomendado)
 *
 * @see DGT-R-000-2024 Resolución de Comprobantes Electrónicos
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Campos en líneas de detalle
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            // BaseImponible - Obligatorio cuando hay impuesto (v4.4)
            if (!Schema::hasColumn('fe_lineas_detalle', 'base_imponible')) {
                $table->decimal('base_imponible', 18, 5)
                    ->nullable()
                    ->after('subtotal')
                    ->comment('Base imponible para cálculo de impuesto (v4.4)');
            }

            // ImpuestoNeto - Impuesto neto de la línea
            if (!Schema::hasColumn('fe_lineas_detalle', 'impuesto_neto')) {
                $table->decimal('impuesto_neto', 18, 5)
                    ->nullable()
                    ->after('impuesto_monto')
                    ->comment('Impuesto neto de la línea (v4.4)');
            }

            // CodigoCABYS - Código de catálogo de bienes y servicios
            if (!Schema::hasColumn('fe_lineas_detalle', 'codigo_cabys')) {
                $table->string('codigo_cabys', 13)
                    ->nullable()
                    ->after('codigo')
                    ->comment('Código CABYS del producto/servicio');
            }
        });

        // Campos adicionales en comprobantes
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            // Tipo de transacción v4.4
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'tipo_transaccion')) {
                $table->string('tipo_transaccion', 2)
                    ->nullable()
                    ->after('medio_pago')
                    ->comment('Código tipo transacción v4.4 (01-13)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            $table->dropColumn(['base_imponible', 'impuesto_neto', 'codigo_cabys']);
        });

        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            $table->dropColumn('tipo_transaccion');
        });
    }
};
