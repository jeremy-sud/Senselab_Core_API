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
        Schema::create('hacienda_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comprobante_id')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->string('clave', 29)->unique();
            $table->char('tipo_comprobante', 2); // 01, 03, 04, 05, 07
            $table->enum('estado', ['pending', 'signed', 'sent', 'accepted', 'rejected', 'error'])->default('pending');
            $table->longText('xml_contenido')->nullable();
            $table->json('respuesta_hacienda')->nullable();
            $table->string('numero_secuencia')->nullable();
            $table->timestamp('fecha_respuesta')->nullable();
            $table->text('mensaje_error')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            // Índices
            $table->index('comprobante_id');
            $table->index('empresa_id');
            $table->index('tipo_comprobante');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hacienda_comprobantes');
    }
};
