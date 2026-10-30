<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade campos para soporte de autoservicio TIG:
     * 1. 'Cambia tu viaje': codigo_boleto, cambiado_a_tiquete_id, motivo_cambio, diferencia_tarifa_cambio.
     * 2. 'Reserva fallida / Auditoría': pasarela_referencia, estado_pago, datos_transaccion.
     * 3. Multi-tenancy estricto (empresa_id) en tiquetes_detalle.
     */
    public function up(): void
    {
        // 1. Ampliar tiquetes_detalle
        if (Schema::hasTable('tiquetes_detalle')) {
            Schema::table('tiquetes_detalle', function (Blueprint $table) {
                if (!Schema::hasColumn('tiquetes_detalle', 'empresa_id')) {
                    $table->unsignedInteger('empresa_id')->nullable()->after('id');
                    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                    $table->index('empresa_id');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'codigo_boleto')) {
                    $table->string('codigo_boleto', 60)->nullable()->after('asiento_numero')
                        ->comment('Código público de boleto (ej. TIG-2026-X8Y9Z)');
                    $table->index('codigo_boleto');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'pasarela_referencia')) {
                    $table->string('pasarela_referencia', 100)->nullable()->after('codigo_boleto')
                        ->comment('Referencia externa del procesador de pagos (BAC, Stripe, Sinpe, etc.)');
                    $table->index('pasarela_referencia');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'estado_pago')) {
                    $table->string('estado_pago', 30)->default('pagado')->after('estado')
                        ->comment('pagado, pendiente, fallido, reembolsado');
                    $table->index('estado_pago');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'cambiado_a_tiquete_id')) {
                    $table->unsignedInteger('cambiado_a_tiquete_id')->nullable()->after('estado_pago');
                    $table->foreign('cambiado_a_tiquete_id')->references('id')->on('tiquetes_detalle')->onDelete('set null');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'motivo_cambio')) {
                    $table->string('motivo_cambio', 255)->nullable()->after('cambiado_a_tiquete_id');
                }

                if (!Schema::hasColumn('tiquetes_detalle', 'diferencia_tarifa_cambio')) {
                    $table->decimal('diferencia_tarifa_cambio', 10, 2)->nullable()->default(0)->after('motivo_cambio')
                        ->comment('Diferencia cobrada o saldo a favor generado por cambio de viaje');
                }
            });

            // Backfill de empresa_id en tiquetes_detalle desde horarios_ruta si aplica
            if (Schema::hasColumn('tiquetes_detalle', 'empresa_id') && Schema::hasTable('horarios_ruta')) {
                DB::statement('
                    UPDATE tiquetes_detalle td
                    INNER JOIN horarios_ruta hr ON td.horario_ruta_id = hr.id
                    SET td.empresa_id = hr.empresa_id
                    WHERE td.empresa_id IS NULL AND hr.empresa_id IS NOT NULL
                ');
            }
        }

        // 2. Ampliar asientos_bloqueos_temporales para pasarela
        if (Schema::hasTable('asientos_bloqueos_temporales')) {
            Schema::table('asientos_bloqueos_temporales', function (Blueprint $table) {
                if (!Schema::hasColumn('asientos_bloqueos_temporales', 'pasarela_referencia')) {
                    $table->string('pasarela_referencia', 100)->nullable()->after('token_bloqueo')
                        ->comment('Referencia de intento de cobro en pasarela');
                    $table->index('pasarela_referencia');
                }

                if (!Schema::hasColumn('asientos_bloqueos_temporales', 'metodo_pago')) {
                    $table->string('metodo_pago', 50)->nullable()->after('pasarela_referencia');
                }

                if (!Schema::hasColumn('asientos_bloqueos_temporales', 'monto_pago')) {
                    $table->decimal('monto_pago', 10, 2)->nullable()->after('metodo_pago');
                }

                if (!Schema::hasColumn('asientos_bloqueos_temporales', 'datos_transaccion')) {
                    $table->json('datos_transaccion')->nullable()->after('monto_pago')
                        ->comment('Payload y metadatos de respuesta de la pasarela bancaria');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asientos_bloqueos_temporales')) {
            Schema::table('asientos_bloqueos_temporales', function (Blueprint $table) {
                if (Schema::hasColumn('asientos_bloqueos_temporales', 'datos_transaccion')) {
                    $table->dropColumn('datos_transaccion');
                }
                if (Schema::hasColumn('asientos_bloqueos_temporales', 'monto_pago')) {
                    $table->dropColumn('monto_pago');
                }
                if (Schema::hasColumn('asientos_bloqueos_temporales', 'metodo_pago')) {
                    $table->dropColumn('metodo_pago');
                }
                if (Schema::hasColumn('asientos_bloqueos_temporales', 'pasarela_referencia')) {
                    $table->dropIndex(['pasarela_referencia']);
                    $table->dropColumn('pasarela_referencia');
                }
            });
        }

        if (Schema::hasTable('tiquetes_detalle')) {
            Schema::table('tiquetes_detalle', function (Blueprint $table) {
                if (Schema::hasColumn('tiquetes_detalle', 'diferencia_tarifa_cambio')) {
                    $table->dropColumn('diferencia_tarifa_cambio');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'motivo_cambio')) {
                    $table->dropColumn('motivo_cambio');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'cambiado_a_tiquete_id')) {
                    $table->dropForeign(['cambiado_a_tiquete_id']);
                    $table->dropColumn('cambiado_a_tiquete_id');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'estado_pago')) {
                    $table->dropIndex(['estado_pago']);
                    $table->dropColumn('estado_pago');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'pasarela_referencia')) {
                    $table->dropIndex(['pasarela_referencia']);
                    $table->dropColumn('pasarela_referencia');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'codigo_boleto')) {
                    $table->dropIndex(['codigo_boleto']);
                    $table->dropColumn('codigo_boleto');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'empresa_id')) {
                    $table->dropForeign(['empresa_id']);
                    $table->dropColumn('empresa_id');
                }
            });
        }
    }
};
