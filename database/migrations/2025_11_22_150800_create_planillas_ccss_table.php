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
        Schema::create('planillas_ccss', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('periodo_nomina_id')->nullable();
            $table->string('periodo', 7)->comment('YYYY-MM');
            $table->date('fecha_generacion');
            $table->date('fecha_presentacion')->nullable();
            $table->string('numero_planilla', 50)->nullable()->comment('Número asignado por CCSS');
            $table->integer('total_empleados');
            $table->decimal('total_salarios', 15, 2);
            $table->decimal('total_cuota_obrera', 15, 2);
            $table->decimal('total_cuota_patronal', 15, 2);
            $table->decimal('total_a_pagar', 15, 2);
            $table->string('archivo_xml', 255)->nullable();
            $table->string('archivo_pdf', 255)->nullable();
            $table->enum('estado', ['borrador', 'enviada', 'aceptada', 'rechazada', 'pagada'])->default('borrador');
            $table->date('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('periodo_nomina_id')->references('id')->on('periodos_nomina')->onDelete('set null');

            // Indexes
            $table->unique(['empresa_id', 'periodo'], 'idx_empresa_periodo');
            $table->index('estado', 'idx_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planillas_ccss');
    }
};
