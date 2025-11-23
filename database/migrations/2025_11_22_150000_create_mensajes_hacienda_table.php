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
        Schema::create('mensajes_hacienda', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('comprobante_id')->nullable()->comment('FK a comprobantes_recibidos_electronicos');
            $table->string('clave_numerica', 50)->comment('Clave numérica del comprobante');
            $table->enum('tipo_mensaje', ['aceptacion', 'rechazo', 'aceptacion_parcial', 'consulta']);
            $table->string('codigo_respuesta', 10)->nullable()->comment('Código de respuesta de Hacienda');
            $table->text('detalle_mensaje')->nullable()->comment('Mensaje detallado de Hacienda');
            $table->longText('xml_respuesta')->nullable()->comment('XML completo de respuesta');
            $table->dateTime('fecha_emision');
            $table->dateTime('fecha_procesamiento')->nullable();
            $table->enum('estado', ['pendiente', 'procesado', 'error'])->default('pendiente');
            $table->integer('intentos_envio')->default(0);
            $table->text('ultimo_error')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('comprobante_id')->references('id')->on('comprobantes_recibidos_electronicos')->onDelete('set null');

            // Indexes
            $table->index(['empresa_id', 'fecha_emision'], 'idx_empresa_fecha');
            $table->index('clave_numerica', 'idx_clave_numerica');
            $table->index('estado', 'idx_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes_hacienda');
    }
};
