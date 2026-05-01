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
        Schema::create('gdpr_deletion_requests', function (Blueprint $table) {
            $table->id();
            
            // Usuario que solicita eliminación
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->string('email')->nullable(); // Por si el user está deletedo
            $table->string('request_type')->nullable(); // 'account', 'data', 'all'
            
            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'failed']);
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('usuarios')->onDelete('set null');
            
            // Datos de la solicitud
            $table->text('reason')->nullable();
            $table->text('scope')->nullable(); // JSON especificando qué datos
            $table->json('data_summary')->nullable(); // Resumen de lo que será eliminado
            
            // Auditoría
            $table->string('ip_address')->nullable();
            $table->json('action_log')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Cumplimiento Legal
            $table->string('gdpr_request_id')->unique()->nullable(); // ID para tracking legal
            $table->boolean('verified_identity')->default(false);
            $table->dateTime('identity_verified_at')->nullable();
            $table->string('verification_method')->nullable();
            
            // Retención temporal
            $table->dateTime('delete_after')->nullable();
            $table->integer('retry_count')->default(0);
            $table->text('last_error')->nullable();
            
            $table->timestamps();
            
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('completed_at');
            $table->index(['status', 'delete_after']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gdpr_deletion_requests');
    }
};
