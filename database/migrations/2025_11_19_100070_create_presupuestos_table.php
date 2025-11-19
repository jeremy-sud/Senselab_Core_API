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
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 255);
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->string('estado', 50)->comment('Borrador, Activo, Finalizado');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();
            
            // Índices y Foreign Keys
            $table->index('empresa_id', 'presupuestos_ibfk_1');
            
            $table->foreign('empresa_id', 'presupuestos_ibfk_1')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
