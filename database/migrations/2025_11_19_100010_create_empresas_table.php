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
        Schema::create('empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 255);
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('razon_social', 255)->nullable();
            $table->string('num_identificacion_dgt', 20);
            $table->string('tipo_identificacion', 2)->nullable();
            $table->string('actividad_economica_principal', 6)->nullable();
            $table->text('direccion')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('canton', 2)->nullable();
            $table->string('distrito', 2)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->longText('certificado_llave_fe')->nullable();
            $table->string('pin_llave_fe_hash', 255)->nullable();
            $table->string('prefijo_orden_compra', 20)->nullable();
            $table->string('moneda_defecto', 3)->default('CRC');
            $table->unsignedInteger('regimen_tributario_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Índices y Foreign Keys
            $table->unique('num_identificacion_dgt', 'nit_ruc');
            $table->unique('email', 'fk_email');
            $table->index('regimen_tributario_id', 'fk_empresa_regimen');
            
            $table->foreign('regimen_tributario_id', 'fk_empresa_regimen')
                  ->references('id')->on('regimenes_tributarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};