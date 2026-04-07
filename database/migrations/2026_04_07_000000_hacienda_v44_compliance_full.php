<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de cumplimiento completo Hacienda v4.4
 *
 * Corrige las 38 brechas identificadas en el análisis comparativo
 * contra la especificación DGT-R-000-2024 v4.4.
 *
 * Brechas Críticas (#1-#8): Correcciones de columnas existentes y campos faltantes
 * Brechas Alta (#9-#19): Campos condicionales-obligatorios
 * Brechas Media (#20-#33): Campos condicionales no implementados
 * Brechas Baja (#34-#38): Campos menores
 *
 * Tablas nuevas:
 * - fe_linea_impuestos: Múltiples impuestos por línea (Brecha #5)
 * - fe_medios_pago: Múltiples medios de pago (Brecha #6)
 * - fe_informacion_referencia: Referencias como tabla (Brecha #14)
 * - fe_otros_cargos: Otros cargos con detalle (Brecha #13)
 * - fe_linea_descuentos: Múltiples descuentos por línea (Brecha #15)
 *
 * @see docs/hacienda/ANALISIS_COMPARATIVO_HACIENDA_V44.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // BRECHAS CRÍTICAS (#1, #7, #8)
        // =====================================================

        // Brecha #1: hacienda_comprobantes.clave VARCHAR(29) → VARCHAR(50)
        Schema::table('hacienda_comprobantes', function (Blueprint $table) {
            $table->string('clave', 50)->change();
        });

        // Brecha #7: empresas.barrio — campo inexistente pero usado en XML
        Schema::table('empresas', function (Blueprint $table) {
            if (!Schema::hasColumn('empresas', 'barrio')) {
                $table->string('barrio', 50)->nullable()->after('distrito')
                    ->comment('Barrio según codificación Hacienda');
            }
            // Brecha #22: Registro fiscal Ley 8707 (bebidas alcohólicas)
            if (!Schema::hasColumn('empresas', 'registro_fiscal_8707')) {
                $table->string('registro_fiscal_8707', 12)->nullable()->after('barrio')
                    ->comment('Registro fiscal Ley 8707 bebidas alcohólicas');
            }
        });

        // Brecha #8: receptor_numero_identificacion VARCHAR(12) → VARCHAR(20)
        // Brecha #4: Receptor ubicación — campos faltantes
        // Brecha #36: receptor_email VARCHAR(100) → VARCHAR(160)
        // Brechas #9, #10, #12, #16, #19, #25, #26: Campos adicionales
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            // Brecha #8: Ampliar identificación receptor a 20 chars
            $table->string('receptor_numero_identificacion', 20)->nullable()->change();

            // Brecha #36: Ampliar email receptor a 160 chars
            $table->string('receptor_email', 160)->nullable()->change();

            // Brecha #4: Ubicación del receptor
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_provincia')) {
                $table->string('receptor_provincia', 1)->nullable()->after('receptor_email')
                    ->comment('Provincia del receptor (1 dígito)');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_canton')) {
                $table->string('receptor_canton', 2)->nullable()->after('receptor_provincia')
                    ->comment('Cantón del receptor (2 dígitos)');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_distrito')) {
                $table->string('receptor_distrito', 2)->nullable()->after('receptor_canton')
                    ->comment('Distrito del receptor (2 dígitos)');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_barrio')) {
                $table->string('receptor_barrio', 50)->nullable()->after('receptor_distrito')
                    ->comment('Barrio del receptor');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_otras_senas')) {
                $table->string('receptor_otras_senas', 250)->nullable()->after('receptor_barrio')
                    ->comment('Dirección detallada del receptor');
            }

            // Brecha #25: OtrasSenasExtranjero
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_otras_senas_extranjero')) {
                $table->string('receptor_otras_senas_extranjero', 300)->nullable()->after('receptor_otras_senas')
                    ->comment('Dirección extranjero no domiciliado');
            }

            // Brecha #12: Receptor nombre comercial
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_nombre_comercial')) {
                $table->string('receptor_nombre_comercial', 80)->nullable()->after('receptor_otras_senas_extranjero')
                    ->comment('Nombre comercial del receptor');
            }

            // Brecha #35: Receptor teléfono
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_telefono_codigo_pais')) {
                $table->string('receptor_telefono_codigo_pais', 3)->nullable()->after('receptor_nombre_comercial')
                    ->comment('Código país teléfono receptor');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'receptor_telefono_numero')) {
                $table->string('receptor_telefono_numero', 20)->nullable()->after('receptor_telefono_codigo_pais')
                    ->comment('Número teléfono receptor');
            }

            // Brecha #9: Código actividad económica receptor
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'codigo_actividad_receptor')) {
                $table->string('codigo_actividad_receptor', 6)->nullable()->after('receptor_telefono_numero')
                    ->comment('Código actividad económica del receptor (obligatorio FEC)');
            }

            // Brecha #10: CondicionVentaOtros
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'condicion_venta_otros')) {
                $table->string('condicion_venta_otros', 100)->nullable()->after('condicion_venta')
                    ->comment('Descripción cuando condicion_venta=99');
            }

            // Brecha #26: Totales No Sujeto
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'total_servicios_no_sujeto')) {
                $table->decimal('total_servicios_no_sujeto', 18, 5)->default(0)->after('total_servicios_exonerados')
                    ->comment('Total servicios no sujetos de IVA');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'total_mercancias_no_sujeta')) {
                $table->decimal('total_mercancias_no_sujeta', 18, 5)->default(0)->after('total_mercancias_exoneradas')
                    ->comment('Total mercancías no sujetas de IVA');
            }
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'total_no_sujeto')) {
                $table->decimal('total_no_sujeto', 18, 5)->default(0)->after('total_exonerado')
                    ->comment('Total no sujeto de IVA');
            }

            // Brecha #16: Total impuestos asumidos emisor/fábrica
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'total_imp_asum_emisor_fabrica')) {
                $table->decimal('total_imp_asum_emisor_fabrica', 18, 5)->default(0)->after('total_impuesto')
                    ->comment('Total impuestos asumidos por emisor/fábrica');
            }

            // Brecha #25: OtrasSenasExtranjero emisor (se guarda en comprobante por si es diferente a empresa)
            if (!Schema::hasColumn('comprobantes_electronicos_fe', 'emisor_otras_senas_extranjero')) {
                $table->string('emisor_otras_senas_extranjero', 300)->nullable()->after('metadata')
                    ->comment('Dirección extranjero emisor no domiciliado');
            }
        });

        // Brecha #2: CodigoDescuento obligatorio cuando hay descuento
        // Brechas #20-#33: Campos adicionales en líneas de detalle
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            // Brecha #2: Código descuento
            if (!Schema::hasColumn('fe_lineas_detalle', 'codigo_descuento')) {
                $table->string('codigo_descuento', 2)->nullable()->after('monto_descuento')
                    ->comment('Código descuento (Nota 20) - OBLIGATORIO cuando hay descuento');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'codigo_descuento_otro')) {
                $table->string('codigo_descuento_otro', 100)->nullable()->after('codigo_descuento')
                    ->comment('Descripción cuando codigo_descuento=99');
            }

            // Brecha #20: Partida arancelaria (exportación)
            if (!Schema::hasColumn('fe_lineas_detalle', 'partida_arancelaria')) {
                $table->string('partida_arancelaria', 12)->nullable()->after('numero_linea')
                    ->comment('Partida arancelaria para exportaciones (FEE)');
            }

            // Brecha #21: TipoTransaccion (ya en migración v4.4 pero no en modelo)
            // Se agrega al fillable del modelo, no necesita columna nueva

            // Brecha #23: Número VIN/Serie
            if (!Schema::hasColumn('fe_lineas_detalle', 'numero_vin_serie')) {
                $table->string('numero_vin_serie', 17)->nullable()->after('detalle')
                    ->comment('Número VIN/Serie para vehículos/aeronaves/embarcaciones');
            }

            // Brecha #24: Registro medicamento y forma farmacéutica
            if (!Schema::hasColumn('fe_lineas_detalle', 'registro_medicamento')) {
                $table->string('registro_medicamento', 100)->nullable()->after('numero_vin_serie')
                    ->comment('Registro sanitario de medicamento');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'forma_farmaceutica')) {
                $table->string('forma_farmaceutica', 3)->nullable()->after('registro_medicamento')
                    ->comment('Código forma farmacéutica (Nota 19)');
            }

            // Brecha #28: FactorCalculoIVA (bienes usados)
            if (!Schema::hasColumn('fe_lineas_detalle', 'factor_calculo_iva')) {
                $table->decimal('factor_calculo_iva', 5, 4)->nullable()->after('impuesto_neto')
                    ->comment('Factor cálculo IVA bienes usados (código 08)');
            }

            // Brecha #32: IVA cobrado en fábrica
            if (!Schema::hasColumn('fe_lineas_detalle', 'iva_cobrado_fabrica')) {
                $table->string('iva_cobrado_fabrica', 2)->nullable()->after('factor_calculo_iva')
                    ->comment('IVA cobrado a nivel de fábrica (Nota 21)');
            }

            // Brecha #16: Impuesto asumido emisor/fábrica por línea
            if (!Schema::hasColumn('fe_lineas_detalle', 'impuesto_asumido_emisor_fabrica')) {
                $table->decimal('impuesto_asumido_emisor_fabrica', 18, 5)->default(0)->after('iva_cobrado_fabrica')
                    ->comment('Impuesto asumido por emisor/fábrica');
            }

            // Brecha #29: Monto exportación
            if (!Schema::hasColumn('fe_lineas_detalle', 'monto_exportacion')) {
                $table->decimal('monto_exportacion', 18, 5)->nullable()->after('impuesto_asumido_emisor_fabrica')
                    ->comment('Monto impuesto exportación (FEE)');
            }

            // Brecha #34: Exoneración campos incompletos
            if (!Schema::hasColumn('fe_lineas_detalle', 'exoneracion_tipo_documento_otro')) {
                $table->string('exoneracion_tipo_documento_otro', 100)->nullable()->after('exoneracion_tipo_documento')
                    ->comment('Descripción cuando exoneracion_tipo_documento=99');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'exoneracion_articulo')) {
                $table->string('exoneracion_articulo', 6)->nullable()->after('exoneracion_numero_documento')
                    ->comment('Artículo de ley para exoneración');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'exoneracion_inciso')) {
                $table->string('exoneracion_inciso', 6)->nullable()->after('exoneracion_articulo')
                    ->comment('Inciso del artículo de exoneración');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'exoneracion_nombre_institucion_otros')) {
                $table->string('exoneracion_nombre_institucion_otros', 160)->nullable()
                    ->after('exoneracion_nombre_institucion')
                    ->comment('Nombre institución cuando exoneracion=99');
            }
            if (!Schema::hasColumn('fe_lineas_detalle', 'exoneracion_tarifa_exonerada')) {
                $table->decimal('exoneracion_tarifa_exonerada', 4, 2)->nullable()->after('exoneracion_porcentaje')
                    ->comment('Puntos de tarifa exonerados');
            }
        });

        // =====================================================
        // TABLAS NUEVAS — Brechas #5, #6, #13, #14, #15
        // =====================================================

        // Brecha #5: Múltiples impuestos por línea {1,1000}
        Schema::create('fe_linea_impuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linea_detalle_id');
            $table->string('codigo', 2)->comment('Código impuesto (Nota 8): 01=IVA, 02=Selectivo, etc.');
            $table->string('codigo_impuesto_otro', 100)->nullable()->comment('Descripción cuando codigo=99');
            $table->string('codigo_tarifa_iva', 2)->nullable()->comment('Código tarifa IVA (Nota 8.1)');
            $table->decimal('tarifa', 4, 2)->nullable()->comment('Porcentaje de tarifa');
            $table->decimal('factor_calculo_iva', 5, 4)->nullable()->comment('Factor para bienes usados (código 08)');
            $table->decimal('monto', 18, 5)->default(0)->comment('Monto del impuesto');
            $table->decimal('monto_exportacion', 18, 5)->nullable()->comment('Monto impuesto exportación (FEE)');
            // Brecha #27: Datos impuesto específico (códigos 03,04,05,06)
            $table->decimal('cantidad_unidad_medida', 7, 2)->nullable()->comment('Cantidad unidad medida impuesto específico');
            $table->decimal('porcentaje', 4, 2)->nullable()->comment('Porcentaje impuesto específico (código 04)');
            $table->decimal('proporcion', 5, 2)->nullable()->comment('Proporción = cantidad × porcentaje');
            $table->decimal('volumen_unidad_consumo', 7, 2)->nullable()->comment('Volumen unidad consumo (código 05)');
            $table->decimal('impuesto_unidad', 18, 5)->nullable()->comment('Monto impuesto por unidad');
            // Exoneración asociada al impuesto
            $table->string('exoneracion_tipo_documento', 2)->nullable()->comment('Tipo doc exoneración');
            $table->string('exoneracion_tipo_documento_otro', 100)->nullable()->comment('Cuando tipo=99');
            $table->string('exoneracion_numero_documento', 40)->nullable();
            $table->string('exoneracion_articulo', 6)->nullable();
            $table->string('exoneracion_inciso', 6)->nullable();
            $table->string('exoneracion_nombre_institucion', 160)->nullable();
            $table->string('exoneracion_nombre_institucion_otros', 160)->nullable();
            $table->dateTime('exoneracion_fecha_emision')->nullable();
            $table->decimal('exoneracion_tarifa_exonerada', 4, 2)->nullable()->comment('Puntos tarifa exonerados');
            $table->decimal('exoneracion_monto', 18, 5)->nullable()->comment('Monto exoneración');
            $table->timestamps();

            $table->foreign('linea_detalle_id')->references('id')->on('fe_lineas_detalle')->onDelete('cascade');
            $table->index('linea_detalle_id');
        });

        // Brecha #6: Múltiples medios de pago {1,4}
        Schema::create('fe_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comprobante_id');
            $table->string('tipo_medio_pago', 2)->comment('Código medio de pago (Nota 6)');
            $table->string('medio_pago_otros', 100)->nullable()->comment('Descripción cuando tipo=99');
            $table->decimal('total_medio_pago', 18, 5)->comment('Monto pagado con este medio');
            $table->timestamps();

            $table->foreign('comprobante_id')->references('id')->on('comprobantes_electronicos_fe')->onDelete('cascade');
            $table->index('comprobante_id');
        });

        // Brecha #14: Información de referencia como tabla independiente
        Schema::create('fe_informacion_referencia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comprobante_id');
            $table->string('tipo_doc', 2)->comment('Tipo documento referencia (Nota 10)');
            $table->string('tipo_doc_otro', 100)->nullable()->comment('Descripción cuando tipo=99');
            $table->string('numero', 50)->nullable()->comment('Clave numérica del documento referenciado');
            $table->dateTime('fecha_emision')->comment('Fecha emisión documento referenciado');
            $table->string('codigo', 2)->nullable()->comment('Código referencia (Nota 9)');
            $table->string('codigo_referencia_otro', 100)->nullable()->comment('Descripción cuando código=99');
            $table->string('razon', 180)->nullable()->comment('Razón de la referencia');
            $table->timestamps();

            $table->foreign('comprobante_id')->references('id')->on('comprobantes_electronicos_fe')->onDelete('cascade');
            $table->index('comprobante_id');
        });

        // Brecha #13: Otros cargos con estructura completa {0,15}
        Schema::create('fe_otros_cargos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comprobante_id');
            $table->string('tipo_documento_oc', 2)->comment('Tipo documento otros cargos (Nota 16)');
            $table->string('tipo_documento_otros', 100)->nullable()->comment('Descripción cuando tipo=99');
            $table->string('tercero_tipo_identificacion', 2)->nullable()->comment('Tipo ID tercero (cuando tipo_documento_oc=04)');
            $table->string('tercero_numero_identificacion', 20)->nullable()->comment('Número ID tercero');
            $table->string('nombre_tercero', 100)->nullable()->comment('Nombre del tercero');
            $table->string('detalle', 160)->comment('Descripción del cargo');
            $table->decimal('porcentaje_oc', 9, 5)->nullable()->comment('Porcentaje del cargo');
            $table->decimal('monto_cargo', 18, 5)->comment('Monto total del cargo');
            $table->timestamps();

            $table->foreign('comprobante_id')->references('id')->on('comprobantes_electronicos_fe')->onDelete('cascade');
            $table->index('comprobante_id');
        });

        // Brecha #15: Múltiples descuentos por línea {0,5}
        Schema::create('fe_linea_descuentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linea_detalle_id');
            $table->integer('orden')->default(1)->comment('Orden 1-5, secuencial');
            $table->decimal('monto_descuento', 18, 5)->comment('Monto del descuento');
            $table->string('codigo_descuento', 2)->comment('Código descuento (Nota 20)');
            $table->string('codigo_descuento_otro', 100)->nullable()->comment('Descripción cuando código=99');
            $table->string('naturaleza_descuento', 80)->nullable()->comment('Naturaleza desc. cuando código=99');
            $table->timestamps();

            $table->foreign('linea_detalle_id')->references('id')->on('fe_lineas_detalle')->onDelete('cascade');
            $table->index('linea_detalle_id');
        });
    }

    public function down(): void
    {
        // Eliminar tablas nuevas
        Schema::dropIfExists('fe_linea_descuentos');
        Schema::dropIfExists('fe_otros_cargos');
        Schema::dropIfExists('fe_informacion_referencia');
        Schema::dropIfExists('fe_medios_pago');
        Schema::dropIfExists('fe_linea_impuestos');

        // Revertir columnas en fe_lineas_detalle
        Schema::table('fe_lineas_detalle', function (Blueprint $table) {
            $columns = [
                'codigo_descuento', 'codigo_descuento_otro', 'partida_arancelaria',
                'numero_vin_serie', 'registro_medicamento', 'forma_farmaceutica',
                'factor_calculo_iva', 'iva_cobrado_fabrica', 'impuesto_asumido_emisor_fabrica',
                'monto_exportacion', 'exoneracion_tipo_documento_otro', 'exoneracion_articulo',
                'exoneracion_inciso', 'exoneracion_nombre_institucion_otros', 'exoneracion_tarifa_exonerada',
            ];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('fe_lineas_detalle', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        // Revertir columnas en comprobantes_electronicos_fe
        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            $columns = [
                'receptor_provincia', 'receptor_canton', 'receptor_distrito', 'receptor_barrio',
                'receptor_otras_senas', 'receptor_otras_senas_extranjero', 'receptor_nombre_comercial',
                'receptor_telefono_codigo_pais', 'receptor_telefono_numero', 'codigo_actividad_receptor',
                'condicion_venta_otros', 'total_servicios_no_sujeto', 'total_mercancias_no_sujeta',
                'total_no_sujeto', 'total_imp_asum_emisor_fabrica', 'emisor_otras_senas_extranjero',
            ];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('comprobantes_electronicos_fe', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        // Revertir columnas en empresas
        Schema::table('empresas', function (Blueprint $table) {
            $columns = ['barrio', 'registro_fiscal_8707'];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('empresas', $col));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        // Revertir cambios de tipo de columna
        Schema::table('hacienda_comprobantes', function (Blueprint $table) {
            $table->string('clave', 29)->change();
        });

        Schema::table('comprobantes_electronicos_fe', function (Blueprint $table) {
            $table->string('receptor_numero_identificacion', 12)->nullable()->change();
            $table->string('receptor_email', 100)->nullable()->change();
        });
    }
};
