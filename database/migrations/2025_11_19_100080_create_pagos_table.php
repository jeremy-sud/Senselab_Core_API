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
        Schema::create('pagos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('orden_compra_id')->nullable();
            $table->unsignedInteger('cuenta_por_pagar_id')->nullable();
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('cuenta_por_cobrar_id')->nullable();
            $table->unsignedInteger('forma_pago_id');
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('USD');
            $table->text('descripcion')->nullable();
            $table->string('referencia', 255)->nullable();
            $table->string('estado', 50);
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
        Schema::dropIfExists('pagos');
    }
};
