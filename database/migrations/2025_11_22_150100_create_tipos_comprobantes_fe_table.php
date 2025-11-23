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
        Schema::create('tipos_comprobantes_fe', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_dgt', 2)->unique()->comment('Código DGT (01, 02, 03, etc)');
            $table->string('nombre', 100)->comment('Factura Electrónica, Nota de Crédito, etc');
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_referencia')->default(false)->comment('Si requiere documento de referencia');
            $table->boolean('permite_exportacion')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('codigo_dgt', 'idx_codigo_dgt');
            $table->index('activo', 'idx_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_comprobantes_fe');
    }
};
