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
        Schema::create('asientos_contables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('numero_asiento', 50);
            $table->date('fecha_asiento');
            $table->enum('tipo_asiento', ['Manual', 'Automático'])->default('Manual');
            $table->string('origen', 100)->nullable()->comment('Venta, Compra, Pago, Cobro, Ajuste, etc.');
            $table->unsignedInteger('documento_origen_id')->nullable()->comment('ID del documento que genera el asiento');
            $table->text('concepto');
            $table->decimal('total_debe', 15, 2)->default(0.00);
            $table->decimal('total_haber', 15, 2)->default(0.00);
            $table->string('estado', 50)->default('Borrador')->comment('Borrador, Confirmado, Anulado');
            $table->unsignedInteger('usuario_id');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');

            $table->unique(['empresa_id', 'numero_asiento'], 'unique_empresa_numero_asiento');
            $table->index('empresa_id');
            $table->index('fecha_asiento');
            $table->index('tipo_asiento');
            $table->index('origen');
            $table->index('estado');
            $table->index('usuario_id');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asientos_contables');
    }
};
