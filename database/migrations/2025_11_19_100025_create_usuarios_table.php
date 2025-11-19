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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 255);
            $table->string('apellidos', 255)->nullable();
            $table->unsignedInteger('cargo_id')->nullable();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->unsignedInteger('empresa_id');
            $table->string('telefono', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->index('empresa_id');
            $table->index('cargo_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index('email');
            
            $table->foreign('cargo_id')->references('id')->on('cargos')->onDelete('set null');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};