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
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id');
            $table->string('banco', 100)->comment('Nombre del banco');
            $table->string('numero_cuenta', 50)->comment('Número de cuenta (encriptado)');
            $table->string('iban', 34)->nullable()->comment('IBAN (CR seguido de 20 dígitos)');
            $table->enum('tipo_cuenta', ['corriente', 'ahorros', 'cliente', 'colones', 'dolares']);
            $table->enum('moneda', ['CRC', 'USD', 'EUR'])->default('CRC');
            $table->decimal('saldo_actual', 15, 2)->default(0);
            $table->unsignedInteger('cuenta_contable_id')->nullable()->comment('Vinculación con contabilidad');
            $table->string('sucursal_banco', 100)->nullable();
            $table->string('contacto_ejecutivo', 100)->nullable();
            $table->string('telefono_ejecutivo', 20)->nullable();
            $table->boolean('activa')->default(true);
            $table->boolean('es_principal')->default(false)->comment('Cuenta principal de la empresa');
            $table->text('notas')->nullable();
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cuenta_contable_id')->references('id')->on('cuentas_contables')->onDelete('set null');

            // Indexes
            $table->unique(['empresa_id', 'numero_cuenta'], 'idx_empresa_numero');
            $table->index('iban', 'idx_iban');
            $table->index('moneda', 'idx_moneda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
