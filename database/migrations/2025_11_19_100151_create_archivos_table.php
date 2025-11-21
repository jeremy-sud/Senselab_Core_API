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
        Schema::create('archivos', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->string('entidad_tipo', 100)->comment('productos, clientes, ventas, etc');
            $table->unsignedInteger('entidad_id');
            $table->string('nombre_original', 255);
            $table->string('nombre_almacenado', 255);
            $table->string('ruta', 500);
            $table->string('tipo_mime', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('categoria', 50)->nullable()->comment('imagen, documento, certificado');
            $table->string('hash_sha256', 64)->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Índices
            $table->index('empresa_id');
            $table->index('usuario_id');
            $table->index('entidad_tipo');
            $table->index('entidad_id');
            $table->index(['entidad_tipo', 'entidad_id'], 'idx_entidad');
            $table->index('categoria');
            $table->index('activo');
            $table->index('eliminado');

            // Foreign Keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
