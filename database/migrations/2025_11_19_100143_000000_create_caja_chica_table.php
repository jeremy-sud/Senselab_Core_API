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
        Schema::create('caja_chica', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 255);
            $table->decimal('monto_inicial', 15, 2);
            $table->decimal('saldo_actual', 15, 2);
            $table->unsignedInteger('responsable_id')->nullable();
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->string('estado', 50)->default('Abierta')->comment('Abierta, Cerrada, Liquidada');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('responsable_id')->references('id')->on('empleados')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('responsable_id');
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
        Schema::dropIfExists('caja_chica');
    }
};
