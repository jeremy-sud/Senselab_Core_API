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
        Schema::create('declaraciones_tributarias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->enum('tipo_declaracion', ['D104', 'D101', 'D103', 'D150', 'D151'])->comment('D104=IVA, D101=Renta');
            $table->string('periodo_fiscal', 7)->comment('YYYY-MM para mensuales, YYYY para anuales');
            $table->date('fecha_inicio_periodo');
            $table->date('fecha_fin_periodo');
            $table->date('fecha_presentacion')->nullable();
            $table->decimal('monto_base_imponible', 15, 2)->default(0);
            $table->decimal('monto_impuesto', 15, 2)->default(0);
            $table->decimal('monto_creditos', 15, 2)->default(0);
            $table->decimal('monto_debitos', 15, 2)->default(0);
            $table->decimal('monto_a_pagar', 15, 2)->default(0);
            $table->decimal('monto_a_favor', 15, 2)->default(0);
            $table->string('numero_confirmacion', 50)->nullable()->comment('Número de confirmación de Hacienda');
            $table->string('archivo_xml', 255)->nullable()->comment('Ruta al XML generado');
            $table->string('archivo_pdf', 255)->nullable()->comment('Ruta al PDF de respaldo');
            $table->enum('estado', ['borrador', 'enviada', 'aceptada', 'rechazada'])->default('borrador');
            $table->text('notas')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

            // Indexes
            $table->unique(['empresa_id', 'tipo_declaracion', 'periodo_fiscal'], 'idx_decl_trib_empresa_tipo_periodo');
            $table->index('periodo_fiscal', 'idx_decl_trib_periodo_fiscal');
            $table->index('estado', 'idx_decl_trib_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaraciones_tributarias');
    }
};
