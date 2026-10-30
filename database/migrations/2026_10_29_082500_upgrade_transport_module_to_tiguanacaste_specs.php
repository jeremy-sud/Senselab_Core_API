<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Actualiza el módulo de transporte para soportar la arquitectura de tiguanacaste.com:
     * 1. Plantilla visual de distribución de asientos (1 y 2 pisos, filas, columnas, pasillos, baños, inactivos).
     * 2. Bloqueo temporal de asientos (holding time de 8-10 minutos contra ventas dobles).
     * 3. Paradas intermedias y matriz de tarifas dinámicas según punto de abordaje y bajada.
     * 4. Multi-tenancy explícito (empresa_id) y trazabilidad completa.
     */
    public function up(): void
    {
        // 1. Plantillas maestras de distribución de asientos
        if (!Schema::hasTable('bus_plantillas_asientos')) {
            Schema::create('bus_plantillas_asientos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id');
                $table->string('nombre', 150)->comment('Ej: Marcopolo 1800 DD - 60 Asientos');
                $table->string('descripcion', 255)->nullable();
                $table->unsignedTinyInteger('pisos')->default(1)->comment('1=Un piso, 2=Doble piso / DD');
                $table->unsignedSmallInteger('total_asientos')->default(0);
                $table->unsignedTinyInteger('filas_piso_1')->default(10);
                $table->unsignedTinyInteger('columnas_piso_1')->default(4);
                $table->unsignedTinyInteger('filas_piso_2')->nullable()->default(0);
                $table->unsignedTinyInteger('columnas_piso_2')->nullable()->default(0);
                $table->boolean('activo')->default(true);
                $table->boolean('eliminado')->default(false);
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->index('empresa_id');
                $table->index('activo');
            });
        }

        // 2. Celdas y asientos individuales (Catálogo espacial)
        if (!Schema::hasTable('bus_asientos')) {
            Schema::create('bus_asientos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('plantilla_id')->nullable();
                $table->unsignedInteger('bus_id')->nullable();
                $table->unsignedTinyInteger('piso')->default(1)->comment('1=Planta Baja / Salón VIP, 2=Planta Alta');
                $table->unsignedSmallInteger('fila')->comment('Número de fila en la rejilla');
                $table->unsignedSmallInteger('columna')->comment('Posición de columna (1..5)');
                $table->string('tipo_elemento', 30)->default('asiento')
                    ->comment('asiento, pasillo, bano, escalera, puerta, cabina, vacio');
                $table->string('numero_asiento', 10)->nullable()->comment('Número visible del asiento (ej. 01, 14, 2A)');
                $table->string('clase', 30)->default('estandar')
                    ->comment('estandar, semi_cama, cama_suite, preferencial_7600');
                $table->string('ubicacion', 20)->nullable()->comment('ventana, pasillo, central');
                $table->boolean('es_asiento')->default(true);
                $table->boolean('activo')->default(true)->comment('false si el asiento físico está roto/inhabilitado');
                $table->boolean('eliminado')->default(false);
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreign('plantilla_id')->references('id')->on('bus_plantillas_asientos')->onDelete('cascade');
                $table->foreign('bus_id')->references('id')->on('buses_unidades')->onDelete('cascade');

                $table->index(['plantilla_id', 'piso', 'fila', 'columna'], 'idx_plantilla_grid');
                $table->index(['bus_id', 'piso', 'fila', 'columna'], 'idx_bus_grid');
                $table->index(['bus_id', 'numero_asiento'], 'idx_bus_asiento_num');
                $table->index('empresa_id');
            });
        }

        // 3. Añadir soporte de plantillas y doble piso a buses_unidades
        Schema::table('buses_unidades', function (Blueprint $table) {
            if (!Schema::hasColumn('buses_unidades', 'cantidad_pisos')) {
                $table->unsignedTinyInteger('cantidad_pisos')->default(1)->after('capacidad_asientos');
            }
            if (!Schema::hasColumn('buses_unidades', 'plantilla_id')) {
                $table->unsignedInteger('plantilla_id')->nullable()->after('modelo_id');
                $table->foreign('plantilla_id')->references('id')->on('bus_plantillas_asientos')
                    ->onDelete('set null');
            }
            if (!Schema::hasColumn('buses_unidades', 'layout_matriz_cache')) {
                $table->json('layout_matriz_cache')->nullable()->after('identificador_interno')
                    ->comment('Caché desnormalizada del plano de asientos para carga instantánea en frontend');
            }
        });

        // 4. Paradas intermedias de ruta (Estaciones de abordaje y bajada)
        if (!Schema::hasTable('ruta_paradas')) {
            Schema::create('ruta_paradas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('ruta_id');
                $table->string('nombre', 255)->comment('Ej: Terminal 7-10 San José, Cruce Esparza, Terminal Nicoya');
                $table->string('codigo', 50)->nullable()->comment('Código de parada o terminal');
                $table->unsignedSmallInteger('orden')->comment('Posición secuencial en la ruta: 1, 2, 3...');
                $table->string('ciudad', 150)->nullable();
                $table->text('direccion')->nullable();
                $table->decimal('latitud', 10, 8)->nullable();
                $table->decimal('longitud', 11, 8)->nullable();
                $table->decimal('distancia_km_origen', 10, 2)->default(0)
                    ->comment('Distancia acumulada en KM desde la salida');
                $table->unsignedSmallInteger('minutos_estimados_origen')->default(0)
                    ->comment('Tiempo estimado en minutos desde la salida');
                $table->boolean('es_terminal')->default(false);
                $table->boolean('permite_abordaje')->default(true);
                $table->boolean('permite_bajada')->default(true);
                $table->boolean('activo')->default(true);
                $table->boolean('eliminado')->default(false);
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreign('ruta_id')->references('id')->on('rutas')->onDelete('cascade');

                $table->unique(['ruta_id', 'orden'], 'unique_ruta_parada_orden');
                $table->index(['ruta_id', 'activo']);
                $table->index('empresa_id');
            });
        }

        // 5. Matriz de tarifas dinámicas por tramo (Origen -> Destino)
        if (!Schema::hasTable('ruta_tarifas_tramos')) {
            Schema::create('ruta_tarifas_tramos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('ruta_id');
                $table->unsignedInteger('parada_origen_id');
                $table->unsignedInteger('parada_destino_id');
                $table->decimal('precio_general', 10, 2)->comment('Tarifa regular ARESEP');
                $table->decimal('precio_adulto_mayor', 10, 2)->default(0)->comment('Tarifa preferencial o Ley 7600');
                $table->decimal('precio_estudiante', 10, 2)->nullable();
                $table->decimal('precio_menor', 10, 2)->nullable();
                $table->string('codigo_resolucion_aresep', 50)->nullable();
                $table->boolean('activo')->default(true);
                $table->boolean('eliminado')->default(false);
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreign('ruta_id')->references('id')->on('rutas')->onDelete('cascade');
                $table->foreign('parada_origen_id')->references('id')->on('ruta_paradas')->onDelete('cascade');
                $table->foreign('parada_destino_id')->references('id')->on('ruta_paradas')->onDelete('cascade');

                $table->unique(['ruta_id', 'parada_origen_id', 'parada_destino_id'], 'unique_ruta_tramo_tarifa');
                $table->index(['ruta_id', 'parada_origen_id'], 'idx_tramo_origen');
                $table->index('empresa_id');
            });
        }

        // 6. Asignar empresa_id a horarios_ruta (Aislamiento Multi-Tenant)
        Schema::table('horarios_ruta', function (Blueprint $table) {
            if (!Schema::hasColumn('horarios_ruta', 'empresa_id')) {
                $table->unsignedInteger('empresa_id')->nullable()->after('id');
                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->index('empresa_id');
            }
        });

        // Backfill de empresa_id en horarios_ruta heredándolo de su ruta respectiva
        if (Schema::hasColumn('horarios_ruta', 'empresa_id') && Schema::hasTable('rutas')) {
            DB::statement('
                UPDATE horarios_ruta hr
                INNER JOIN rutas r ON hr.ruta_id = r.id
                SET hr.empresa_id = r.empresa_id
                WHERE hr.empresa_id IS NULL
            ');
        }

        // 7. Bloqueos temporales de asientos (Holding Time 8-10 min)
        if (!Schema::hasTable('asientos_bloqueos_temporales')) {
            Schema::create('asientos_bloqueos_temporales', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('horario_ruta_id');
                $table->string('asiento_numero', 10);
                $table->unsignedInteger('bus_asiento_id')->nullable();
                $table->unsignedInteger('parada_origen_id')->nullable();
                $table->unsignedInteger('parada_destino_id')->nullable();
                $table->string('token_bloqueo', 100)->unique()
                    ->comment('UUID del hold generado durante el checkout');
                $table->string('session_id', 100)->nullable()
                    ->comment('ID de sesión frontend / carrito');
                $table->unsignedInteger('usuario_id')->nullable()
                    ->comment('ID del usuario/cajero si es venta en taquilla ERP');
                $table->unsignedInteger('cliente_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('estado', 30)->default('bloqueado')
                    ->comment('bloqueado, comprado, liberado, expirado');
                $table->timestamp('bloqueado_en')->useCurrent();
                $table->timestamp('expira_en')
                    ->comment('Límite de holding time (ej. NOW() + 10 minutos)');
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
                $table->foreign('horario_ruta_id')->references('id')->on('horarios_ruta')->onDelete('cascade');
                $table->foreign('bus_asiento_id')->references('id')->on('bus_asientos')->onDelete('set null');
                $table->foreign('parada_origen_id')->references('id')->on('ruta_paradas')->onDelete('set null');
                $table->foreign('parada_destino_id')->references('id')->on('ruta_paradas')->onDelete('set null');
                $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
                $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');

                $table->index(['horario_ruta_id', 'asiento_numero', 'expira_en'], 'idx_hold_activo');
                $table->index(['horario_ruta_id', 'estado', 'expira_en'], 'idx_hold_estado');
                $table->index('expira_en');
                $table->index('empresa_id');
            });
        }

        // 8. Trazabilidad de paradas y holding en tiquetes_detalle
        Schema::table('tiquetes_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('tiquetes_detalle', 'parada_origen_id')) {
                $table->unsignedInteger('parada_origen_id')->nullable()->after('horario_ruta_id');
                $table->foreign('parada_origen_id')->references('id')->on('ruta_paradas')->onDelete('set null');
            }
            if (!Schema::hasColumn('tiquetes_detalle', 'parada_destino_id')) {
                $table->unsignedInteger('parada_destino_id')->nullable()->after('parada_origen_id');
                $table->foreign('parada_destino_id')->references('id')->on('ruta_paradas')->onDelete('set null');
            }
            if (!Schema::hasColumn('tiquetes_detalle', 'tipo_tarifa')) {
                $table->string('tipo_tarifa', 30)->default('general')->after('precio_final_tiquete')
                    ->comment('general, adulto_mayor, estudiante, menor');
            }
            if (!Schema::hasColumn('tiquetes_detalle', 'bloqueo_temporal_id')) {
                $table->unsignedInteger('bloqueo_temporal_id')->nullable()->after('estado');
                $table->foreign('bloqueo_temporal_id')->references('id')->on('asientos_bloqueos_temporales')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tiquetes_detalle')) {
            Schema::table('tiquetes_detalle', function (Blueprint $table) {
                if (Schema::hasColumn('tiquetes_detalle', 'bloqueo_temporal_id')) {
                    $table->dropForeign(['bloqueo_temporal_id']);
                    $table->dropColumn('bloqueo_temporal_id');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'tipo_tarifa')) {
                    $table->dropColumn('tipo_tarifa');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'parada_destino_id')) {
                    $table->dropForeign(['parada_destino_id']);
                    $table->dropColumn('parada_destino_id');
                }
                if (Schema::hasColumn('tiquetes_detalle', 'parada_origen_id')) {
                    $table->dropForeign(['parada_origen_id']);
                    $table->dropColumn('parada_origen_id');
                }
            });
        }

        Schema::dropIfExists('asientos_bloqueos_temporales');

        if (Schema::hasTable('horarios_ruta')) {
            Schema::table('horarios_ruta', function (Blueprint $table) {
                if (Schema::hasColumn('horarios_ruta', 'empresa_id')) {
                    $table->dropForeign(['empresa_id']);
                    $table->dropColumn('empresa_id');
                }
            });
        }

        Schema::dropIfExists('ruta_tarifas_tramos');
        Schema::dropIfExists('ruta_paradas');

        if (Schema::hasTable('buses_unidades')) {
            Schema::table('buses_unidades', function (Blueprint $table) {
                if (Schema::hasColumn('buses_unidades', 'layout_matriz_cache')) {
                    $table->dropColumn('layout_matriz_cache');
                }
                if (Schema::hasColumn('buses_unidades', 'plantilla_id')) {
                    $table->dropForeign(['plantilla_id']);
                    $table->dropColumn('plantilla_id');
                }
                if (Schema::hasColumn('buses_unidades', 'cantidad_pisos')) {
                    $table->dropColumn('cantidad_pisos');
                }
            });
        }

        Schema::dropIfExists('bus_asientos');
        Schema::dropIfExists('bus_plantillas_asientos');
    }
};
