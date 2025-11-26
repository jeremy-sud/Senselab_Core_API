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
        Schema::create('fe_certificados_digitales', function (Blueprint $table) {
            $table->id();
            
            // Relación con empresa
            $table->unsignedInteger('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            
            // Información del certificado
            $table->string('nombre', 200)->comment('Nombre descriptivo del certificado');
            $table->string('tipo', 20)->default('p12')->comment('p12, pfx, pem');
            $table->string('numero_serie', 100)->nullable()->comment('Número de serie del certificado');
            $table->string('emisor', 255)->nullable()->comment('Entidad emisora del certificado');
            $table->string('sujeto', 255)->nullable()->comment('Sujeto del certificado (CN)');
            
            // Ruta al archivo del certificado
            $table->string('ruta_archivo', 500)->comment('Ruta en storage al archivo .p12');
            $table->text('password_encrypted')->nullable()->comment('Contraseña encriptada del certificado');
            
            // Validez del certificado
            $table->date('fecha_emision')->nullable()->comment('Fecha de emisión');
            $table->date('fecha_vencimiento')->nullable()->comment('Fecha de vencimiento');
            $table->boolean('activo')->default(true)->index()->comment('Si está activo para uso');
            $table->boolean('valido')->default(true)->comment('Si es válido (no expirado)');
            
            // Ambiente
            $table->string('ambiente', 20)->default('sandbox')->comment('sandbox o production');
            
            // Metadatos
            $table->json('metadata')->nullable()->comment('Información adicional');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['empresa_id', 'activo']);
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_certificados_digitales');
    }
};
