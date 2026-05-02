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
        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->id();
            
            // Identificación de la política
            $table->string('name')->unique();
            $table->text('description')->nullable();
            
            // Qué datos aplican
            $table->string('table_name');
            $table->json('columns')->nullable(); // Si es específico a columnas
            $table->json('conditions')->nullable(); // WHERE clauses JSON
            
            // Cuánto tiempo retener
            $table->integer('retention_days')->default(365);
            $table->string('retention_period')->nullable(); // 'days', 'months', 'years'
            
            // Qué hacer cuando expira
            $table->enum('action_on_expiry', ['soft_delete', 'hard_delete', 'archive', 'anonymize']);
            $table->string('archive_location')->nullable();
            
            // Configuración de anonimización
            $table->json('anonymize_columns')->nullable();
            $table->text('anonymize_strategy')->nullable(); // hash, null, fake_data, etc
            
            // Control
            $table->boolean('enabled')->default(true);
            $table->boolean('auto_execute')->default(true);
            $table->string('cron_expression')->default('0 2 * * *'); // 2 AM daily
            
            // Auditoría
            $table->dateTime('last_execution_at')->nullable();
            $table->integer('rows_affected')->nullable();
            $table->text('last_error')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('usuarios')->onDelete('set null');
            
            $table->timestamps();
            
            $table->index('enabled');
            $table->index('table_name');
            $table->index('last_execution_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_retention_policies');
    }
};
