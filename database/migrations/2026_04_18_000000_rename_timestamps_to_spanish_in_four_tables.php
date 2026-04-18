<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DT-1: Renombra timestamps de inglés a español en 4 tablas
 * que no seguían la convención del proyecto (creado_en / actualizado_en).
 *
 * Tablas afectadas: zonas_geograficas, cuentas_bancarias,
 * planillas_ccss, movimientos_bancarios.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [
        'zonas_geograficas',
        'cuentas_bancarias',
        'planillas_ccss',
        'movimientos_bancarios',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('created_at', 'creado_en');
                $t->renameColumn('updated_at', 'actualizado_en');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('creado_en', 'created_at');
                $t->renameColumn('actualizado_en', 'updated_at');
            });
        }
    }
};
