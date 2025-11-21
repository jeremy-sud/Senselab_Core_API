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
        Schema::create('configuraciones_api', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('clave', 100);
            $table->text('valor');
            $table->string('tipo', 50)->default('string')->comment('string, json, int, bool, encrypted');
            $table->string('categoria', 100)->comment('hacienda, email, sms, api_externa');
            $table->text('descripcion')->nullable();
            $table->boolean('encriptado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Índices
            $table->unique(['empresa_id', 'clave'], 'unique_empresa_clave');
            $table->index('empresa_id');
            $table->index('categoria');
            $table->index('activo');

            // Foreign Keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_api');
    }
};
