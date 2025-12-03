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
        Schema::create('url_shorter_db', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->text('url_original');
            $table->string('url_corta', 100)->unique();
            $table->string('slug', 50)->unique();
            $table->unsignedInteger('clicks')->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->timestamp('expira_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            
            // Índices
            $table->index('empresa_id', 'idx_url_shorter_empresa');
            $table->index('usuario_id', 'idx_url_shorter_usuario');
            $table->index('slug', 'idx_url_shorter_slug');
            $table->index('activo', 'idx_url_shorter_activo');
            $table->index('eliminado', 'idx_url_shorter_eliminado');
            
            // Foreign Keys
            $table->foreign('empresa_id', 'fk_url_shorter_empresa')
                  ->references('id')->on('empresas')
                  ->onDelete('cascade');
            
            $table->foreign('usuario_id', 'fk_url_shorter_usuario')
                  ->references('id')->on('usuarios')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_shorter_db');
    }
};
