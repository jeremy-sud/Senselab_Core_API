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
        Schema::create('codigos_actividad_economica', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->comment('Código de actividad económica');
            $table->string('descripcion', 255);
            $table->string('categoria_principal', 100)->nullable()->comment('Ej: Comercio, Servicios, Industria');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('codigo', 'idx_codigo');
            $table->fullText('descripcion', 'idx_fulltext_descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_actividad_economica');
    }
};
