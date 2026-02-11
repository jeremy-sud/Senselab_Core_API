# Plan de Llenado de Tablas - api_db (Testing Data)

## 📋 Estrategia

Llenar **todas las tablas** con datos realistas en orden de dependencias (relaciones):

### Fase 1: Tablas Maestras Básicas (Sin FK)
1. ✅ **empresas** - 2-3 empresas Demo
2. ✅ **roles** - 7-8 roles del sistema
3. ✅ **permisos** - Permisos por módulo
4. ✅ **tipos_cuentas** - Tipos contables
5. ✅ **tipos_clientes** - Tipo de cliente
6. ✅ **unidades_medida** - Unidades producto
7. ✅ **regimenes_tributarios** - Regímenes fiscales
8. ✅ **formas_pago** - Formas de pago
9. ✅ **tipos_impuesto** - Impuestos
10. ✅ **codigos_actividad_economica** - CIIU
11. ✅ **tipos_comprobantes_fe** - Tipos de comprobante
12. ✅ **cabys** - Códigos CABYS

### Fase 2: Tablas Estructurales (Dependen de Fase 1)
1. ✅ **usuarios** - Admin + trabajadores
2. ✅ **sucursales** - Sucursales de empresa
3. ✅ **almacenes** - Almacenes de sucursal
4. ✅ **cargos** - Cargos empleados
5. ✅ **cuentas_contables** - Plan de cuentas

### Fase 3: Tablas de Catálogos Productos
1. ✅ **categorias_productos** - Categorías
2. ✅ **marcas** - Marcas productos
3. ✅ **productos** - Productos (10-15)
4. ✅ **inventario_productos** - Stock por almacén

### Fase 4: Tablas de Personas
1. ✅ **clientes** - 5-10 clientes
2. ✅ **proveedores** - 5-10 proveedores
3. ✅ **empleados** - 5-8 empleados
4. ✅ **zonas_geograficas** - Zonas geográficas

### Fase 5: Transacciones Compras/Ventas
1. ✅ **ordenes_compra** - 3-5 órdenes
2. ✅ **detalle_ordenes_compra** - Detalles de compra
3. ✅ **ventas** - 5-8 ventas
4. ✅ **detalle_ventas** - Detalles de venta

### Fase 6: Inventario y Movimientos
1. ✅ **entradas_inventario** - Entradas
2. ✅ **detalle_entradas_inventario** - Detalles
3. ✅ **salidas_inventario** - Salidas
4. ✅ **detalle_salidas_inventario** - Detalles

### Fase 7: Contabilidad
1. ✅ **cuentas_por_cobrar** - Cuentas por cobrar
2. ✅ **cuentas_por_pagar** - Cuentas por pagar
3. ✅ **asientos_contables** - Asientos
4. ✅ **detalle_asientos** - Detalles asientos

### Fase 8: Nómina
1. ✅ **periodos_nomina** - Períodos de nómina
2. ✅ **nomina_empleados** - Nóminas
3. ✅ **deducciones_legales** - Deducciones
4. ✅ **pagos_nomina** - Pagos de nómina

### Fase 9: Facturación Electrónica
1. ✅ **consecutivos_fe** - Consecutivos
2. ✅ **comprobantes_electronicos_fe** - Comprobantes
3. ✅ **fe_lineas_detalle** - Líneas de detalle
4. ✅ **mensajes_hacienda** - Mensajes

### Fase 10: Otras Tablas
1. ✅ **cajas** - Cajas registradoras
2. ✅ **caja_chica** - Cajas chicas
3. ✅ **movimientos_caja_chica** - Movimientos
4. ✅ **presupuestos** - Presupuestos
5. ✅ **notificaciones** - Notificaciones
6. ✅ **archivos** - Archivos
7. ✅ **logs_acceso_sistema** - Logs acceso
8. ✅ **auditoria_actividades** - Auditoría
9. ✅ **rutas** - Rutas de transporte
10. ✅ **horarios_ruta** - Horarios
11. ✅ **buses_unidades** - Unidades de bus
12. ✅ **roles_permisos** - Relación roles-permisos
13. ✅ **rol_usuario** - Relación usuario-roles

### Fase 11: Tablas Transaccionales Avanzadas
1. ✅ **cuentas_bancarias** - Cuentas bancarias
2. ✅ **movimientos_bancarios** - Movimientos
3. ✅ **tipos_cambio_historial** - Tipos de cambio
4. ✅ **planillas_ccss** - Planillas CCSS
5. ✅ **retenciones_impuestos** - Retenciones
6. ✅ **tasas_impuesto** - Tasas de impuesto
7. ✅ **configuraciones** - Configuraciones
8. ✅ **configuraciones_api** - Config API
9. ✅ **comprobantes_recibidos_electronicos** - Comprobantes recibidos
10. ✅ **declaraciones_tributarias** - Declaraciones
11. ✅ **fe_certificados_digitales** - Certificados
12. ✅ **fe_oauth_tokens** - Tokens OAuth
13. ✅ **modelos_buses** - Modelos de bus
14. ✅ **entidad_etiquetas** - Etiquetas
15. ✅ **etiquetas** - Definiciones etiquetas
16. ✅ **url_shorter_db** - URLs acortadas

## 📊 Resumen de Datos

| Tabla | Registros | Tipo |
|-------|----------|------|
| **Maestras** | 150+ | Catálogos |
| **Estructurales** | 30+ | Configuración |
| **Productos** | 15 | Inventario |
| **Personas** | 28 | Contactos |
| **Transacciones** | 50+ | Operación |
| **Contabilidad** | 40+ | Finanzas |
| **Nómina** | 20+ | RH |
| **FE** | 15 | Facturación |
| **Misc** | 100+ | Otros |
| **TOTAL** | 500+ | Datos Falsos |

## 🔄 Orden de Ejecución

1. Ejecutar seeders existentes del proyecto
2. Llenar tablas faltantes con SQL directo
3. Verificar integridad referencial
4. Hacer dump de la data

## 🚀 Próximos Pasos

1. Crear script SQL comprensivo
2. Ejecutarlo contra api_db
3. Validar datos
4. Crear backup

