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
        Schema::create('salidas_inventario', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('almacen_id')->nullable();
            $table->dateTime('fecha_salida');
            $table->string('tipo_salida', 50)->nullable()->comment('Venta, Ajuste Negativo, Devolución Proveedor, Transferencia, Consumo Interno');
            $table->unsignedInteger('venta_id')->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->string('documento_referencia', 100)->nullable();
            $table->string('estado', 50)->default('Pendiente');
            $table->decimal('monto_total', 15, 2)->default(0.00);
            $table->text('observaciones')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('almacen_id')->references('id')->on('almacenes')->onDelete('set null');
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('set null');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('almacen_id');
            $table->index('venta_id');
            $table->index('cliente_id');
            $table->index('proveedor_id');
            $table->index('fecha_salida');
            $table->index('tipo_salida');
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
        Schema::dropIfExists('salidas_inventario');
    }
};
