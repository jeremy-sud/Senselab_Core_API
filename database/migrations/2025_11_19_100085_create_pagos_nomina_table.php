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
        Schema::create('pagos_nomina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('empleado_id');
            $table->unsignedInteger('periodo_nomina_id')->nullable();
            $table->dateTime('fecha_pago');
            $table->decimal('monto_bruto', 10, 2);
            $table->decimal('total_deducciones', 10, 2);
            $table->decimal('monto_neto_pagado', 10, 2);
            $table->unsignedInteger('metodo_pago_id')->nullable();
            $table->string('referencia_pago', 100)->nullable();
            $table->string('estado', 50)->default('pagado');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_nomina');
    }
};
