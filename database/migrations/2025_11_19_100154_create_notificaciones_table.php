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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('empresa_id');
            $table->string('tipo', 50)->comment('info, warning, error, success');
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->json('datos')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_en')->nullable();
            $table->string('url', 500)->nullable();
            $table->tinyInteger('prioridad')->default(0)->comment('0=normal, 1=alta, 2=urgente');
            $table->timestamp('creado_en')->useCurrent();

            // Índices
            $table->index('usuario_id');
            $table->index('empresa_id');
            $table->index('tipo');
            $table->index('leida');
            $table->index('prioridad');
            $table->index('creado_en');
            $table->index(['usuario_id', 'leida'], 'idx_usuario_leida');

            // Foreign Keys
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
