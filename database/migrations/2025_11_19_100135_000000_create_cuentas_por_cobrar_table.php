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
        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('cliente_id');
            $table->unsignedInteger('venta_id')->nullable();
            $table->string('numero_documento', 100);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_original', 15, 2);
            $table->decimal('monto_pendiente', 15, 2);
            $table->decimal('monto_pagado', 15, 2)->default(0.00);
            $table->string('estado', 50)->default('Pendiente')->comment('Pendiente, Pagada Parcial, Pagada, Vencida, Anulada');
            $table->string('moneda', 3)->default('CRC');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('restrict');
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('cliente_id');
            $table->index('venta_id');
            $table->index('fecha_emision');
            $table->index('fecha_vencimiento');
            $table->index('estado');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_cobrar');
    }
};
