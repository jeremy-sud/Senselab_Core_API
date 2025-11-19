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
        Schema::create('periodos_nomina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre_periodo', 255);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago')->nullable();
            $table->string('estado', 50)->default('Abierto')->comment('Abierto, Procesado, Pagado, Cerrado');
            $table->decimal('total_salarios', 15, 2)->default(0.00);
            $table->decimal('total_deducciones', 15, 2)->default(0.00);
            $table->decimal('total_neto', 15, 2)->default(0.00);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

            $table->index('empresa_id');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
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
        Schema::dropIfExists('periodos_nomina');
    }
};
