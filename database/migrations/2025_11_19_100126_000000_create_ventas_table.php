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
        Schema::create('ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('sucursal_id');
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('usuario_id');
            $table->dateTime('fecha_venta');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('tipo_comprobante', 50)->nullable();
            $table->string('serie_comprobante', 20)->nullable();
            $table->string('numero_comprobante', 50)->nullable();
            $table->string('clave_numerica_hacienda', 50)->nullable()->unique();
            $table->string('consecutivo_hacienda', 20)->nullable();
            $table->string('moneda', 3)->default('CRC');
            $table->decimal('subtotal_bruto_total', 12, 2)->default(0.00);
            $table->decimal('monto_descuento_total', 12, 2)->default(0.00);
            $table->decimal('subtotal_neto_total', 12, 2)->default(0.00);
            $table->decimal('monto_impuesto_total', 12, 2)->default(0.00);
            $table->decimal('monto_total_venta', 10, 2);
            $table->string('estado_venta', 50);
            $table->string('condicion_pago', 100)->nullable();
            $table->string('condicion_venta_dgt', 2)->nullable();
            $table->integer('plazo_credito_dias')->default(0);
            $table->text('observaciones')->nullable();
            $table->longText('xml_enviado')->nullable();
            $table->longText('xml_respuesta_hacienda')->nullable();
            $table->string('estado_hacienda', 20)->default('Pendiente');
            $table->string('tipo_referencia_doc', 2)->nullable();
            $table->string('clave_referencia_doc', 50)->nullable();
            $table->unsignedInteger('forma_pago_id');
            $table->dateTime('fecha_emision_hacienda')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursales')->onDelete('restrict');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('restrict');
            $table->foreign('forma_pago_id')->references('id')->on('formas_pago')->onDelete('restrict');

            $table->unique(['empresa_id', 'tipo_comprobante', 'serie_comprobante', 'numero_comprobante'], 'unique_comprobante');
            $table->index('empresa_id');
            $table->index('sucursal_id');
            $table->index('cliente_id');
            $table->index('usuario_id');
            $table->index('forma_pago_id');
            $table->index('fecha_venta');
            $table->index('estado_venta');
            $table->index('estado_hacienda');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
