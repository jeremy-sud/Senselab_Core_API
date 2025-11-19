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
        Schema::create('nomina_empleados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('periodo_nomina_id');
            $table->unsignedInteger('empleado_id');
            $table->decimal('salario_bruto', 15, 2)->default(0.00);
            $table->decimal('horas_extras', 10, 2)->default(0.00);
            $table->decimal('monto_horas_extras', 15, 2)->default(0.00);
            $table->decimal('bonificaciones', 15, 2)->default(0.00);
            $table->decimal('total_devengado', 15, 2)->default(0.00);
            $table->decimal('deducciones_ccss', 15, 2)->default(0.00);
            $table->decimal('deducciones_impuesto_renta', 15, 2)->default(0.00);
            $table->decimal('otras_deducciones', 15, 2)->default(0.00);
            $table->decimal('total_deducciones', 15, 2)->default(0.00);
            $table->decimal('salario_neto', 15, 2)->default(0.00);
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('periodo_nomina_id')->references('id')->on('periodos_nomina')->onDelete('cascade');
            $table->foreign('empleado_id')->references('id')->on('empleados')->onDelete('restrict');

            $table->unique(['periodo_nomina_id', 'empleado_id'], 'unique_periodo_empleado');
            $table->index('periodo_nomina_id');
            $table->index('empleado_id');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_empleados');
    }
};
