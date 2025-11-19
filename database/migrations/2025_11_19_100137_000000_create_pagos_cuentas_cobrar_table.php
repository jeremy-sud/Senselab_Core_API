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
        Schema::create('pagos_cuentas_cobrar', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cuenta_por_cobrar_id');
            $table->unsignedInteger('forma_pago_id');
            $table->date('fecha_pago');
            $table->decimal('monto_pago', 15, 2);
            $table->string('numero_referencia', 100)->nullable();
            $table->string('moneda', 3)->default('CRC');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('cuenta_por_cobrar_id')->references('id')->on('cuentas_por_cobrar')->onDelete('cascade');
            $table->foreign('forma_pago_id')->references('id')->on('formas_pago')->onDelete('restrict');

            $table->index('cuenta_por_cobrar_id');
            $table->index('forma_pago_id');
            $table->index('fecha_pago');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_cuentas_cobrar');
    }
};
