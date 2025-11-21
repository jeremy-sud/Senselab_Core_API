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
        Schema::create('sesiones_usuarios', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedInteger('usuario_id');
            $table->string('token_hash', 255);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('ultimo_acceso')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->useCurrent();

            // Índices
            $table->index('usuario_id');
            $table->index('token_hash');
            $table->index('activo');
            $table->index('ultimo_acceso');
            $table->index(['usuario_id', 'activo'], 'idx_usuario_activo');

            // Foreign Keys
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_usuarios');
    }
};
