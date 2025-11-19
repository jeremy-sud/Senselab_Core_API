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
        Schema::create('tipos_cambio_historial', function (Blueprint $table) {
            $table->increments('id');
            $table->date('fecha');
            $table->char('moneda_origen', 3)->default('USD');
            $table->char('moneda_destino', 3)->default('CRC');
            $table->decimal('tasa_compra', 12, 5);
            $table->decimal('tasa_venta', 12, 5);
            $table->string('fuente', 50)->default('BCCR');
            $table->timestamp('creado_en')->useCurrent();

            $table->unique(['fecha', 'moneda_origen', 'moneda_destino'], 'unique_tipo_cambio_fecha');
            $table->index('fecha');
            $table->index(['moneda_origen', 'moneda_destino']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_cambio_historial');
    }
};
