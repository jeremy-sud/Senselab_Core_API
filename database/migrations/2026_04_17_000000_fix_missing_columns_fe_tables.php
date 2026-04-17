<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agregar columnas faltantes en tablas FE.
 *
 * Corrige discrepancias entre controlador/modelo y esquema de BD:
 * - fe_lineas_detalle: tipo_transaccion (existe en $fillable, faltaba en BD)
 * - comprobantes_electronicos_fe: observaciones (usado por controller, faltaba en BD)
 * - fe_linea_impuestos: columnas para impuestos específicos (dato_especifico_*)
 *   y campos correctos (codigo_tarifa, exoneracion_porcentaje, impuesto_asumido_emisor_fabrica)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. fe_lineas_detalle — tipo_transaccion faltante
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('fe_lineas_detalle', 'tipo_transaccion')) {
                $table->string('tipo_transaccion', 2)
                    ->nullable()
                    ->after('unidad_medida_comercial')
                    ->comment('Tipo transacción según Nota 5.1 Hacienda v4.4');
            }
        });

        // 2. comprobantes_electronicos_fe — observaciones faltante
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'observaciones')) {
                $table->text('observaciones')
                    ->nullable()
                    ->after('tipo_cambio')
                    ->comment('Observaciones generales del comprobante');
            }
        });

        // 3. fe_linea_impuestos — columnas faltantes para impuestos específicos y compatibilidad
        Schema::table('fe_linea_impuestos', function (Blueprint $table) {
            // codigo_tarifa genérico (además de codigo_tarifa_iva)
            if (!Schema::hasColumn('fe_linea_impuestos', 'codigo_tarifa')) {
                $table->string('codigo_tarifa', 2)
                    ->nullable()
                    ->after('codigo_impuesto_otro')
                    ->comment('Código tarifa genérico (Nota 8.1)');
            }

            // impuesto_asumido_emisor_fabrica
            if (!Schema::hasColumn('fe_linea_impuestos', 'impuesto_asumido_emisor_fabrica')) {
                $table->decimal('impuesto_asumido_emisor_fabrica', 18, 5)
                    ->nullable()
                    ->after('monto_exportacion')
                    ->comment('Impuesto asumido por emisor/fábrica');
            }

            // Datos impuesto específico (combustibles, alcohol, etc.)
            if (!Schema::hasColumn('fe_linea_impuestos', 'dato_especifico_codigo')) {
                $table->string('dato_especifico_codigo', 2)
                    ->nullable()
                    ->after('impuesto_unidad')
                    ->comment('Código de dato específico');
            }
            if (!Schema::hasColumn('fe_linea_impuestos', 'dato_especifico_tipo_gravamen')) {
                $table->string('dato_especifico_tipo_gravamen', 2)
                    ->nullable()
                    ->after('dato_especifico_codigo')
                    ->comment('Tipo gravamen dato específico');
            }
            if (!Schema::hasColumn('fe_linea_impuestos', 'dato_especifico_unidad_medida')) {
                $table->string('dato_especifico_unidad_medida', 10)
                    ->nullable()
                    ->after('dato_especifico_tipo_gravamen')
                    ->comment('Unidad medida dato específico');
            }
            if (!Schema::hasColumn('fe_linea_impuestos', 'dato_especifico_cantidad_base')) {
                $table->decimal('dato_especifico_cantidad_base', 18, 5)
                    ->nullable()
                    ->after('dato_especifico_unidad_medida')
                    ->comment('Cantidad base dato específico');
            }
            if (!Schema::hasColumn('fe_linea_impuestos', 'dato_especifico_monto_gravamen')) {
                $table->decimal('dato_especifico_monto_gravamen', 18, 5)
                    ->nullable()
                    ->after('dato_especifico_cantidad_base')
                    ->comment('Monto gravamen dato específico');
            }

            // exoneracion_porcentaje (alias para legacy, mapea a exoneracion_tarifa_exonerada)
            if (!Schema::hasColumn('fe_linea_impuestos', 'exoneracion_porcentaje')) {
                $table->decimal('exoneracion_porcentaje', 4, 2)
                    ->nullable()
                    ->after('exoneracion_tarifa_exonerada')
                    ->comment('Porcentaje exonerado (alias legacy de tarifa_exonerada)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            if (Schema::hasColumn('fe_lineas_detalle', 'tipo_transaccion')) {
                $table->dropColumn('tipo_transaccion');
            }
        });

        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            if (Schema::hasColumn('comprobantes_electronicos_fe', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });

        Schema::table('fe_linea_impuestos', function (Blueprint $table) {
            $columns = [
                'codigo_tarifa',
                'impuesto_asumido_emisor_fabrica',
                'dato_especifico_codigo',
                'dato_especifico_tipo_gravamen',
                'dato_especifico_unidad_medida',
                'dato_especifico_cantidad_base',
                'dato_especifico_monto_gravamen',
                'exoneracion_porcentaje',
            ];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('fe_linea_impuestos', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
