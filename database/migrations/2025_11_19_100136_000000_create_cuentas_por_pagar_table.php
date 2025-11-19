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
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('proveedor_id');
            $table->unsignedInteger('orden_compra_id')->nullable();
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
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('restrict');
            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('proveedor_id');
            $table->index('orden_compra_id');
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
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
