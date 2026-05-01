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
        Schema::create('movimiento_presupuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->unsignedInteger('sucursal_id')->nullable();
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('set null');
            
            $table->unsignedInteger('detalle_presupuesto_id');
            $table->foreign('detalle_presupuesto_id')->references('id')->on('detalle_presupuestos')->onDelete('cascade');
            
            $table->decimal('monto', 15, 2);
            $table->date('fecha');
            $table->string('descripcion');
            $table->enum('tipo', ['ejecucion', 'ajuste_positivo', 'ajuste_negativo'])->default('ejecucion');
            
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            
            $table->unsignedInteger('usuario_creacion_id')->nullable();
            $table->foreign('usuario_creacion_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->unsignedInteger('usuario_modificacion_id')->nullable();
            $table->foreign('usuario_modificacion_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->unsignedInteger('usuario_eliminacion_id')->nullable();
            $table->foreign('usuario_eliminacion_id')->references('id')->on('usuarios')->onDelete('set null');
            
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('eliminado_en')->nullable();
            
            // Índices
            $table->index(['empresa_id', 'detalle_presupuesto_id']);
            $table->index(['referencia_tipo', 'referencia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_presupuestos');
    }
};
