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
        Schema::create('entidad_etiquetas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('etiqueta_id');
            $table->string('entidad_tipo', 50)->comment('Nombre de la tabla: clientes, productos, ventas, empleados, etc.');
            $table->unsignedInteger('entidad_id')->comment('ID del registro en la tabla mencionada en entidad_tipo');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();
            
            // Índices y Foreign Keys
            $table->unique(['etiqueta_id', 'entidad_tipo', 'entidad_id'], 'uq_entidad_etiqueta');
            $table->index('etiqueta_id', 'entidad_etiquetas_ibfk_1');
            
            $table->foreign('etiqueta_id', 'entidad_etiquetas_ibfk_1')
                  ->references('id')->on('etiquetas')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entidad_etiquetas');
    }
};
