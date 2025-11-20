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
        Schema::create('tipos_impuesto', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo_hacienda', 10)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->text('comentario')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('activo');
            $table->index('eliminado');
            $table->index('codigo_hacienda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_impuesto');
    }
};
