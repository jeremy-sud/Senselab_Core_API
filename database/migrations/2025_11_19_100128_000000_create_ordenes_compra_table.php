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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('proveedor_id');
            $table->unsignedInteger('usuario_id');
            $table->string('numero_orden', 50);
            $table->date('fecha_orden');
            $table->date('fecha_entrega_esperada')->nullable();
            $table->string('moneda', 3)->default('CRC');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('impuesto_total', 15, 2)->default(0.00);
            $table->decimal('total_orden', 15, 2);
            $table->string('estado', 50)->default('Pendiente');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('restrict');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');

            $table->unique(['empresa_id', 'numero_orden'], 'unique_empresa_numero_orden');
            $table->index('empresa_id');
            $table->index('proveedor_id');
            $table->index('usuario_id');
            $table->index('fecha_orden');
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
        Schema::dropIfExists('ordenes_compra');
    }
};
