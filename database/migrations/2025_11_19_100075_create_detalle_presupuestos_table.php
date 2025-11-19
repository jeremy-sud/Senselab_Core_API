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
        Schema::create('detalle_presupuestos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('presupuesto_id');
            $table->unsignedInteger('cuenta_contable_id');
            $table->decimal('monto_presupuestado', 15, 2);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();
            
            // Índices y Foreign Keys
            $table->index('presupuesto_id', 'detalle_presupuestos_ibfk_1');
            $table->index('cuenta_contable_id', 'detalle_presupuestos_ibfk_2');
            
            $table->foreign('presupuesto_id', 'detalle_presupuestos_ibfk_1')
                  ->references('id')->on('presupuestos')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            $table->foreign('cuenta_contable_id', 'detalle_presupuestos_ibfk_2')
                  ->references('id')->on('cuentas_contables')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_presupuestos');
    }
};
