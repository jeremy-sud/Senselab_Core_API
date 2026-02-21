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
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->onDelete('set null');
            
            $table->foreignId('detalle_presupuesto_id')->constrained('detalle_presupuestos')->onDelete('cascade');
            
            $table->decimal('monto', 15, 2);
            $table->date('fecha');
            $table->string('descripcion');
            $table->enum('tipo', ['ejecucion', 'ajuste_positivo', 'ajuste_negativo'])->default('ejecucion');
            
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            
            $table->foreignId('usuario_creacion_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignId('usuario_modificacion_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->foreignId('usuario_eliminacion_id')->nullable()->constrained('usuarios')->onDelete('set null');
            
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
