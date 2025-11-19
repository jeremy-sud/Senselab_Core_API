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
        Schema::create('cuentas_contables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('codigo', 50);
            $table->unsignedInteger('tipo_cuenta_id')->nullable();
            $table->unsignedInteger('cuenta_padre_id')->nullable();
            $table->boolean('permite_movimientos')->default(true);
            $table->decimal('saldo_actual', 15, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable()->useCurrent();
            $table->timestamp('actualizado_en')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Índices y Foreign Keys
            $table->index(['empresa_id', 'codigo'], 'cuentas_contables_ibfk_1');
            $table->index('cuenta_padre_id', 'fk_ctacont_padre');
            $table->index('tipo_cuenta_id', 'fk_ctacont_tipocuenta_final');
            
            $table->foreign('cuenta_padre_id', 'cuentas_contables_ibfk_2')
                  ->references('id')->on('cuentas_contables')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            $table->foreign('cuenta_padre_id', 'fk_ctacont_padre')
                  ->references('id')->on('cuentas_contables')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            
            $table->foreign('tipo_cuenta_id', 'fk_ctacont_tipocuenta_final')
                  ->references('id')->on('tipos_cuentas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
            
            $table->foreign('empresa_id', 'fk_cuentacont_empresa')
                  ->references('id')->on('empresas')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_contables');
    }
};
