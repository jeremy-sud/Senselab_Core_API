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
        Schema::create('entradas_inventario', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('almacen_id')->nullable();
            $table->dateTime('fecha_entrada');
            $table->string('tipo_entrada', 50)->nullable()->comment('Compra, Ajuste Positivo, Transferencia, Devolución Cliente, Producción');
            $table->unsignedInteger('orden_compra_id')->nullable();
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
            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra')->onDelete('set null');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('almacen_id');
            $table->index('orden_compra_id');
            $table->index('proveedor_id');
            $table->index('fecha_entrada');
            $table->index('tipo_entrada');
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
        Schema::dropIfExists('entradas_inventario');
    }
};
