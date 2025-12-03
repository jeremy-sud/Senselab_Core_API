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
        Schema::create('retenciones_impuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('proveedor_id')->nullable()->comment('Proveedor al que se le retuvo');
            $table->unsignedInteger('compra_id')->nullable()->comment('FK a ordenes_compra o pagos');
            $table->unsignedInteger('venta_id')->nullable();
            $table->enum('tipo_retencion', ['renta', 'iva', 'otras']);
            $table->decimal('porcentaje_retencion', 5, 2)->comment('Ej: 2.00, 10.00, 15.00');
            $table->decimal('monto_base', 15, 2)->comment('Monto sobre el que se calcula');
            $table->decimal('monto_retenido', 15, 2);
            $table->string('numero_comprobante', 50)->nullable()->comment('Número de comprobante de retención');
            $table->date('fecha_retencion');
            $table->string('periodo_declaracion', 7)->comment('YYYY-MM para vincular a declaración');
            $table->boolean('declarado')->default(false)->comment('Si ya fue incluido en declaración');
            $table->text('notas')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');

            // Indexes
            $table->index(['empresa_id', 'periodo_declaracion'], 'idx_ret_imp_empresa_periodo');
            $table->index('proveedor_id', 'idx_ret_imp_proveedor');
            $table->index('fecha_retencion', 'idx_ret_imp_fecha');
            $table->index('declarado', 'idx_ret_imp_declarado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retenciones_impuestos');
    }
};
