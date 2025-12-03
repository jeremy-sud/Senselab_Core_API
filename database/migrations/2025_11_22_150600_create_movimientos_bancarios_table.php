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
        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuenta_bancaria_id');
            $table->unsignedInteger('empresa_id');
            $table->date('fecha_movimiento');
            $table->date('fecha_valor')->nullable();
            $table->enum('tipo_movimiento', ['deposito', 'retiro', 'transferencia_entrada', 'transferencia_salida', 'comision', 'interes', 'ajuste']);
            $table->string('numero_referencia', 50)->nullable()->comment('Número de cheque, transferencia, etc');
            $table->string('descripcion', 255);
            $table->decimal('monto', 15, 2);
            $table->decimal('saldo_despues', 15, 2)->nullable()->comment('Saldo después del movimiento');
            $table->string('beneficiario', 200)->nullable();
            $table->boolean('conciliado')->default(false);
            $table->date('fecha_conciliacion')->nullable();
            $table->unsignedInteger('asiento_contable_id')->nullable()->comment('Vinculación con contabilidad');
            $table->text('notas')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('cuenta_bancaria_id')->references('id')->on('cuentas_bancarias')->onDelete('cascade');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('asiento_contable_id')->references('id')->on('asientos_contables')->onDelete('set null');

            // Indexes
            $table->index(['cuenta_bancaria_id', 'fecha_movimiento'], 'idx_mov_banc_cuenta_fecha');
            $table->index('conciliado', 'idx_mov_banc_conciliado');
            $table->index('tipo_movimiento', 'idx_mov_banc_tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
    }
};
