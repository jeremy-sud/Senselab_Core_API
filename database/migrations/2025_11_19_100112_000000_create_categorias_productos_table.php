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
        Schema::create('categorias_productos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 255)->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('categoria_padre_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('categoria_padre_id')->references('id')->on('categorias_productos')->onDelete('set null');

            $table->index('categoria_padre_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_productos');
    }
};
