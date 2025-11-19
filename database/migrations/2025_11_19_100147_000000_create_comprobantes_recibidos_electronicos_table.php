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
        Schema::create('comprobantes_recibidos_electronicos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->string('clave_numerica', 50)->unique();
            $table->string('consecutivo', 20)->nullable();
            $table->date('fecha_emision');
            $table->string('tipo_documento', 50);
            $table->string('numero_cedula_emisor', 50);
            $table->string('nombre_emisor', 255);
            $table->decimal('monto_total', 15, 2);
            $table->decimal('monto_impuesto', 15, 2)->default(0.00);
            $table->string('moneda', 3)->default('CRC');
            $table->longText('xml_original')->nullable();
            $table->string('estado_validacion', 50)->default('Recibido');
            $table->string('mensaje_hacienda', 50)->nullable()->comment('Aceptado, Aceptado Parcial, Rechazado');
            $table->text('detalle_mensaje')->nullable();
            $table->boolean('contabilizado')->default(false);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');

            $table->index('empresa_id');
            $table->index('proveedor_id');
            $table->index('fecha_emision');
            $table->index('estado_validacion');
            $table->index('mensaje_hacienda');
            $table->index('contabilizado');
            $table->index('activo');
            $table->index('eliminado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes_recibidos_electronicos');
    }
};
