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
        Schema::create('auditoria_actividades', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedInteger('usuario_id')->nullable();
            $table->unsignedInteger('empresa_id');
            $table->string('accion', 50)->comment('crear, actualizar, eliminar, login');
            $table->string('tabla', 100);
            $table->unsignedInteger('registro_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            // Índices
            $table->index('usuario_id');
            $table->index('empresa_id');
            $table->index('accion');
            $table->index('tabla');
            $table->index('registro_id');
            $table->index(['tabla', 'registro_id'], 'idx_tabla_registro');
            $table->index('creado_en');

            // Foreign Keys
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_actividades');
    }
};
