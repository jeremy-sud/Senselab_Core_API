<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creación de tabla de auditoría para FASE 1.7
 *
 * Almacena todos los cambios realizados en registros auditados
 * incluyendo usuario, timestamp, valores anteriores/nuevos, IP, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Identificación del evento
            $table->string('event'); // 'created', 'updated', 'deleted', 'restored'
            $table->string('model_type'); // Clase del modelo (e.g., App\Models\Usuario)
            $table->unsignedBigInteger('model_id'); // ID del registro auditado
            $table->string('model_name')->nullable(); // Nombre legible del modelo

            // Usuario responsable
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();

            // Cambios
            $table->json('old_values')->nullable(); // Valores anteriores
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->json('changed_fields')->nullable(); // Array de campos que cambiaron

            // Contexto de la solicitud
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('http_method')->nullable();
            $table->text('url')->nullable();
            $table->string('route_action')->nullable(); // Controller@method

            // Empresa/Tenant
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('tenant_id')->nullable();

            // Metadatos
            $table->text('description')->nullable(); // Descripción legible del cambio
            $table->json('metadata')->nullable(); // Datos adicionales
            $table->integer('execution_time_ms')->nullable(); // Tiempo de ejecución

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Índices para búsqueda rápida
            $table->index('event');
            $table->index('model_type');
            $table->index('model_id');
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('empresa_id');
            $table->index('created_at');
            $table->fullText(['description']); // Búsqueda de texto completo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
