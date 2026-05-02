<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('url', 2048);
            $table->json('eventos');
            $table->string('secret', 64);
            $table->string('descripcion', 500)->nullable();
            $table->unsignedSmallInteger('timeout_segundos')->default(30);
            $table->unsignedTinyInteger('max_reintentos')->default(3);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index(['empresa_id', 'activo']);
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
