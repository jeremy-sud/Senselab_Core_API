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
        Schema::create('movimientos_caja_chica', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('caja_chica_id');
            $table->date('fecha_movimiento');
            $table->enum('tipo_movimiento', ['Ingreso', 'Egreso', 'Reembolso', 'Ajuste']);
            $table->decimal('monto', 15, 2);
            $table->string('numero_comprobante', 100)->nullable();
            $table->text('concepto');
            $table->unsignedInteger('cuenta_contable_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('caja_chica_id')->references('id')->on('caja_chica')->onDelete('cascade');
            $table->foreign('cuenta_contable_id')->references('id')->on('cuentas_contables')->onDelete('set null');

            $table->index('caja_chica_id');
            $table->index('fecha_movimiento');
            $table->index('tipo_movimiento');
            $table->index('cuenta_contable_id');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja_chica');
    }
};
