<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Tablas que tienen columna empresa_id sin foreign key explícita.
     * Se agregan índices faltantes y FKs hacia empresas(id) si no existen.
     * Política: ON UPDATE CASCADE, ON DELETE RESTRICT para no borrar datos históricos accidentalmente.
     */
    private array $empresaTables = [
        'almacenes','archivos','asientos_contables','buses_unidades','caja_chica','clientes',
        'comprobantes_recibidos_electronicos','configuraciones','configuraciones_api','consecutivos_fe',
        'cuentas_bancarias','cuentas_contables','cuentas_por_cobrar','cuentas_por_pagar','declaraciones_tributarias',
        'empleados','entradas_inventario','etiquetas','mensajes_hacienda','movimientos_bancarios','notificaciones',
        'ordenes_compra','pagos','pagos_nomina','periodos_nomina','planillas_ccss','presupuestos','productos',
        'proveedores','retenciones_impuestos','rutas','salidas_inventario','sucursales','url_shorter_db','usuarios',
        'ventas','zonas_geograficas'
    ];

    /**
     * Columnas a indexar si falta índice: (tabla => columnas simples)
     */
    private array $simpleIndexes = [
        'pagos' => ['empresa_id'],
        'pagos_nomina' => ['empresa_id'],
        'logs_acceso_sistema' => ['email'], // mejora búsquedas por email en auditoría
    ];

    public function up(): void
    {
        // Agregar índices simples si no existen
        foreach ($this->simpleIndexes as $table => $columns) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $col) {
                    // Verificar si ya existe índice sobre la columna
                    $exists = DB::selectOne("SELECT COUNT(1) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]);
                    if (($exists->c ?? 0) === 0) {
                        $blueprint->index($col, $table.'_'.$col.'_idx');
                    }
                }
            });
        }

        // Agregar FKs a empresa_id si no existen ya
        foreach ($this->empresaTables as $table) {
            if (!Schema::hasTable($table)) { continue; }
            // Validar existencia de columna empresa_id
            $hasEmpresaId = DB::selectOne("SELECT COUNT(1) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'empresa_id'", [$table]);
            if (($hasEmpresaId->c ?? 0) === 0) { continue; }

            // Verificar que no haya huérfanos (si hubiera, se omite para no romper)
            $orphans = DB::selectOne("SELECT COUNT(*) AS c FROM `{$table}` t LEFT JOIN empresas e ON t.empresa_id = e.id WHERE e.id IS NULL");
            if (($orphans->c ?? 0) > 0) {
                // Registrar advertencia en log para tratamiento manual
                logger()->warning("[Migración FKs] Se encontraron {$orphans->c} registros huérfanos en {$table}, se omite FK.");
                continue;
            }

            // Verificar si ya existe la FK
            $fkExists = DB::selectOne("SELECT COUNT(1) AS c FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME='empresa_id' AND REFERENCED_TABLE_NAME='empresas'", [$table]);
            if (($fkExists->c ?? 0) > 0) { continue; }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // Asegurar índice para empresa_id para FK (si no lo hay)
                $idxExists = DB::selectOne("SELECT COUNT(1) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME='empresa_id'", [$table]);
                if (($idxExists->c ?? 0) === 0) {
                    $blueprint->index('empresa_id', $table.'_empresa_id_idx');
                }
                $blueprint->foreign('empresa_id', $table.'_empresa_id_foreign')
                    ->references('id')->on('empresas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        // Revertir solo FKs e índices agregados (sin eliminar otros existentes)
        foreach ($this->empresaTables as $table) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                // Foreign key
                $blueprint->dropForeign([$table.'_empresa_id_foreign']); // Silencioso si no existe
                // Índice creado por la migración (nombre consistente)
                if (Schema::hasColumn($table, 'empresa_id')) {
                    try { $blueprint->dropIndex($table.'_empresa_id_idx'); } catch (Throwable $e) {}
                }
            });
        }

        foreach ($this->simpleIndexes as $table => $columns) {
            if (!Schema::hasTable($table)) { continue; }
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $col) {
                    try { $blueprint->dropIndex($table.'_'.$col.'_idx'); } catch (Throwable $e) {}
                }
            });
        }
    }
};
