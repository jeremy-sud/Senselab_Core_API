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
        Schema::create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('categoria_id')->nullable();
            $table->string('codigo', 100);
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('unidad_medida_id')->nullable();
            $table->unsignedInteger('marca_id')->nullable();
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->unsignedInteger('tipo_impuesto_id')->nullable();
            $table->unsignedInteger('cabys_id')->nullable();
            $table->decimal('precio_compra', 15, 2)->default(0.00);
            $table->decimal('precio_venta', 15, 2)->default(0.00);
            $table->decimal('stock_minimo', 15, 2)->default(0.00);
            $table->decimal('stock_maximo', 15, 2)->default(0.00);
            $table->enum('tipo_producto', ['Producto', 'Servicio'])->default('Producto');
            $table->boolean('vende')->default(true);
            $table->boolean('compra')->default(true);
            $table->boolean('controla_inventario')->default(true);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('categorias_productos')->onDelete('set null');
            $table->foreign('unidad_medida_id')->references('id')->on('unidades_medida')->onDelete('set null');
            $table->foreign('marca_id')->references('id')->on('marcas')->onDelete('set null');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
            $table->foreign('tipo_impuesto_id')->references('id')->on('tipos_impuesto')->onDelete('set null');
            $table->foreign('cabys_id')->references('id')->on('cabys')->onDelete('set null');

            $table->unique(['empresa_id', 'codigo'], 'unique_empresa_codigo_producto');
            $table->index('empresa_id');
            $table->index('categoria_id');
            $table->index('unidad_medida_id');
            $table->index('marca_id');
            $table->index('proveedor_id');
            $table->index('tipo_impuesto_id');
            $table->index('cabys_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index('tipo_producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
