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
        Schema::create('deducciones_legales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique()->comment('CCSS, INS, LPT, etc');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['ccss_obrero', 'ccss_patronal', 'ins_laboral', 'ins_lpt', 'impuesto_renta', 'asociacion_solidarista', 'embargo', 'prestamo', 'otros']);
            $table->decimal('porcentaje_base', 5, 2)->nullable()->comment('Porcentaje si es fijo');
            $table->decimal('monto_fijo', 10, 2)->nullable()->comment('Monto si es fijo');
            $table->enum('aplica_sobre', ['salario_bruto', 'salario_neto', 'monto_especifico'])->default('salario_bruto');
            $table->boolean('es_obligatoria')->default(false);
            $table->boolean('activa')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('tipo', 'idx_tipo');
            $table->index('activa', 'idx_activa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deducciones_legales');
    }
};
