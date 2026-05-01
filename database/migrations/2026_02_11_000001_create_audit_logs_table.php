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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Usuario que realizó la acción
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();
            
            // Modelo auditado
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->index(['auditable_type', 'auditable_id']);
            
            // Acción realizada
            $table->enum('action', ['created', 'updated', 'deleted', 'restored', 'forceDeleted']);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            
            // Datos de la solicitud
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_method')->nullable();
            $table->string('request_path')->nullable();
            
            // Datos sensibles
            $table->boolean('involves_sensitive_data')->default(false);
            $table->string('sensitive_fields_mask')->nullable(); // JSON de campos sensibles
            
            // Retención de datos
            $table->dateTime('retention_expires_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            
            // Auditoria de la auditoria
            $table->string('change_reason')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indices para búsqueda y reporting
            $table->index('created_at');
            $table->index('user_id');
            $table->index('action');
            $table->index('involves_sensitive_data');
            $table->index('retention_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
