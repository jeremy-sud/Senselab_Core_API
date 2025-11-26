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
        Schema::create('comprobantes_electronicos_fe', function (Blueprint $table) {
            $table->id();
            
            // Relación con empresa emisora
            $table->unsignedInteger('empresa_id');
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            
            // Información del comprobante
            $table->string('tipo_documento', 2)->comment('01=Factura, 02=NotaDébito, 03=NotaCrédito, 04=Tiquete');
            $table->string('clave', 50)->unique()->comment('Clave numérica única de 50 posiciones');
            $table->string('consecutivo', 20)->comment('Número consecutivo interno');
            $table->dateTime('fecha_emision')->comment('Fecha y hora de emisión del comprobante');
            
            // Información del receptor (opcional para algunos tipos)
            $table->string('receptor_tipo_identificacion', 2)->nullable();
            $table->string('receptor_numero_identificacion', 12)->nullable();
            $table->string('receptor_nombre', 200)->nullable();
            $table->string('receptor_email', 100)->nullable();
            
            // Montos y totales
            $table->string('moneda', 3)->default('CRC')->comment('ISO 4217: CRC, USD, EUR');
            $table->decimal('tipo_cambio', 18, 5)->default(1.00000)->comment('Tipo de cambio si moneda != CRC');
            $table->decimal('total_servicios_gravados', 18, 5)->default(0)->comment('Subtotal servicios gravados');
            $table->decimal('total_servicios_exentos', 18, 5)->default(0)->comment('Subtotal servicios exentos');
            $table->decimal('total_servicios_exonerados', 18, 5)->default(0)->comment('Subtotal servicios exonerados');
            $table->decimal('total_mercancias_gravadas', 18, 5)->default(0)->comment('Subtotal mercancías gravadas');
            $table->decimal('total_mercancias_exentas', 18, 5)->default(0)->comment('Subtotal mercancías exentas');
            $table->decimal('total_mercancias_exoneradas', 18, 5)->default(0)->comment('Subtotal mercancías exoneradas');
            $table->decimal('total_gravado', 18, 5)->default(0)->comment('Total gravado');
            $table->decimal('total_exento', 18, 5)->default(0)->comment('Total exento');
            $table->decimal('total_exonerado', 18, 5)->default(0)->comment('Total exonerado');
            $table->decimal('total_venta', 18, 5)->default(0)->comment('Subtotal antes de impuestos');
            $table->decimal('total_descuentos', 18, 5)->default(0)->comment('Total descuentos');
            $table->decimal('total_venta_neta', 18, 5)->default(0)->comment('Venta neta después de descuentos');
            $table->decimal('total_impuesto', 18, 5)->default(0)->comment('Total impuestos (IVA)');
            $table->decimal('total_iva_devuelto', 18, 5)->default(0)->comment('IVA devuelto (solo para exportaciones)');
            $table->decimal('total_otros_cargos', 18, 5)->default(0)->comment('Otros cargos');
            $table->decimal('total_comprobante', 18, 5)->default(0)->comment('Total final del comprobante');
            
            // Condiciones de venta
            $table->string('condicion_venta', 2)->default('01')->comment('01=Contado, 02=Crédito, etc.');
            $table->string('medio_pago', 2)->default('01')->comment('01=Efectivo, 02=Tarjeta, etc.');
            $table->integer('plazo_credito')->nullable()->comment('Días de crédito si condicion_venta=02');
            
            // XML generado y firmado
            $table->longText('xml_original')->nullable()->comment('XML generado según esquema v4.3');
            $table->longText('xml_firmado')->nullable()->comment('XML firmado con XAdES-EPES');
            
            // Estado y seguimiento
            $table->string('estado', 20)->default('pendiente')->index()
                ->comment('pendiente|enviando|recibido|procesando|aceptado|rechazado|error');
            $table->string('situacion', 1)->default('1')->comment('1=Normal, 2=Contingencia, 3=Sin internet');
            
            // Respuestas de Hacienda
            $table->longText('respuesta_hacienda_xml')->nullable()->comment('XML de respuesta de Hacienda');
            $table->text('mensaje_hacienda')->nullable()->comment('Mensaje de aceptación/rechazo');
            $table->string('codigo_respuesta_hacienda', 10)->nullable()->comment('Código de respuesta');
            
            // Timestamps de control
            $table->timestamp('fecha_envio')->nullable()->comment('Cuándo se envió a Hacienda');
            $table->timestamp('fecha_recibido')->nullable()->comment('Cuándo Hacienda confirmó recepción');
            $table->timestamp('fecha_procesado')->nullable()->comment('Cuándo Hacienda procesó');
            $table->timestamp('fecha_respuesta')->nullable()->comment('Cuándo se recibió respuesta final');
            
            // Reintentos y errores
            $table->integer('intentos_envio')->default(0)->comment('Número de intentos de envío');
            $table->timestamp('ultimo_intento')->nullable();
            $table->text('ultimo_error')->nullable()->comment('Último error en caso de fallo');
            
            // Metadatos adicionales
            $table->json('metadata')->nullable()->comment('Datos adicionales en formato JSON');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para optimización
            $table->index(['empresa_id', 'estado']);
            $table->index(['empresa_id', 'fecha_emision']);
            $table->index(['tipo_documento', 'estado']);
            $table->index('consecutivo');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes_electronicos_fe');
    }
};
