<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->unsignedInteger('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->string('evento', 100);
            $table->string('estado', 20)->default('pendiente'); // pendiente, exitoso, fallido
            $table->unsignedSmallInteger('codigo_respuesta')->nullable();
            $table->unsignedInteger('latencia_ms')->nullable();
            $table->unsignedInteger('payload_size')->nullable();
            $table->json('payload')->nullable();
            $table->text('respuesta')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('intento')->default(1);
            $table->timestamp('proximo_reintento_en')->nullable();
            $table->timestamp('creado_en')->useCurrent();

            $table->index(['webhook_id', 'estado']);
            $table->index(['empresa_id', 'creado_en']);
            $table->index('evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
