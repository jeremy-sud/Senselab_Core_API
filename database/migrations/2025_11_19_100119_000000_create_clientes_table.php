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
        Schema::create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('tipo_identificacion', 10);
            $table->string('numero_identificacion', 50);
            $table->string('nombre', 255);
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('canton', 100)->nullable();
            $table->string('distrito', 100)->nullable();
            $table->decimal('limite_credito', 15, 2)->default(0.00);
            $table->integer('plazo_credito_dias')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

            $table->unique(['empresa_id', 'tipo_identificacion', 'numero_identificacion'], 'unique_cliente_identificacion');
            $table->index('empresa_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index(['tipo_identificacion', 'numero_identificacion']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
