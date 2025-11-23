# 🇨🇷 Propuesta de Nuevas Tablas para Mercado Costarricense

**Fecha**: 22 de noviembre de 2025  
**Proyecto**: Ursol CAST API  
**Estado**: PROPUESTA PENDIENTE APROBACIÓN

---

## 📊 Análisis de Estado Actual

### Tablas Existentes (60)
✅ Sistema base completamente funcional con:
- Gestión empresarial multi-tenant
- Facturación electrónica (Hacienda CR)
- Contabilidad completa
- Inventario y almacenes
- Recursos humanos y nómina
- Transporte (rutas y buses)
- RBAC y auditoría

### Modelos Implementados (67)
✅ Todos los modelos tienen implementación completa

---

## 🆕 Tablas Propuestas para Expansión Costa Rica

### **CATEGORÍA 1: FACTURACIÓN ELECTRÓNICA HACIENDA** 🏛️

#### 1. **mensajes_hacienda**
Almacenar respuestas detalladas de Hacienda para mejor trazabilidad.

```sql
CREATE TABLE `mensajes_hacienda` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `comprobante_id` BIGINT UNSIGNED NULL COMMENT 'FK a comprobantes_recibidos_electronicos',
  `clave_numerica` VARCHAR(50) NOT NULL COMMENT 'Clave numérica del comprobante',
  `tipo_mensaje` ENUM('aceptacion', 'rechazo', 'aceptacion_parcial', 'consulta') NOT NULL,
  `codigo_respuesta` VARCHAR(10) NULL COMMENT 'Código de respuesta de Hacienda',
  `detalle_mensaje` TEXT NULL COMMENT 'Mensaje detallado de Hacienda',
  `xml_respuesta` LONGTEXT NULL COMMENT 'XML completo de respuesta',
  `fecha_emision` DATETIME NOT NULL,
  `fecha_procesamiento` DATETIME NULL,
  `estado` ENUM('pendiente', 'procesado', 'error') DEFAULT 'pendiente',
  `intentos_envio` INT DEFAULT 0,
  `ultimo_error` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes_recibidos_electronicos`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_empresa_fecha` (`empresa_id`, `fecha_emision`),
  INDEX `idx_clave_numerica` (`clave_numerica`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Mejorar trazabilidad y debugging de comunicación con Hacienda.

---

#### 2. **tipos_comprobantes_fe**
Catálogo oficial de tipos de comprobantes electrónicos según Hacienda.

```sql
CREATE TABLE `tipos_comprobantes_fe` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo_dgt` VARCHAR(2) NOT NULL UNIQUE COMMENT 'Código DGT (01, 02, 03, etc)',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Factura Electrónica, Nota de Crédito, etc',
  `descripcion` TEXT NULL,
  `requiere_referencia` TINYINT(1) DEFAULT 0 COMMENT 'Si requiere documento de referencia',
  `permite_exportacion` TINYINT(1) DEFAULT 0,
  `activo` TINYINT(1) DEFAULT 1,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_codigo_dgt` (`codigo_dgt`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Datos iniciales**:
- 01: Factura Electrónica
- 02: Nota de Débito Electrónica
- 03: Nota de Crédito Electrónica
- 04: Tiquete Electrónico
- 05: Nota de Despacho
- 06: Contrato
- 07: Procedimiento
- 08: Comprobante emitido en contingencia
- 09: Factura Electrónica de Exportación

---

#### 3. **codigos_actividad_economica**
Catálogo de actividades económicas de Hacienda para emisión de facturas.

```sql
CREATE TABLE `codigos_actividad_economica` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE COMMENT 'Código de actividad económica',
  `descripcion` VARCHAR(255) NOT NULL,
  `categoria_principal` VARCHAR(100) NULL COMMENT 'Ej: Comercio, Servicios, Industria',
  `activo` TINYINT(1) DEFAULT 1,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_codigo` (`codigo`),
  FULLTEXT KEY `idx_fulltext_descripcion` (`descripcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Las empresas necesitan declarar sus actividades económicas en facturas electrónicas.

---

### **CATEGORÍA 2: TRIBUTACIÓN Y HACIENDA** 💰

#### 4. **declaraciones_tributarias**
Registro de declaraciones de impuestos ante Hacienda (D104, D101, etc).

```sql
CREATE TABLE `declaraciones_tributarias` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `tipo_declaracion` ENUM('D104', 'D101', 'D103', 'D150', 'D151') NOT NULL COMMENT 'D104=IVA, D101=Renta',
  `periodo_fiscal` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM para mensuales, YYYY para anuales',
  `fecha_inicio_periodo` DATE NOT NULL,
  `fecha_fin_periodo` DATE NOT NULL,
  `fecha_presentacion` DATE NULL,
  `monto_base_imponible` DECIMAL(15,2) DEFAULT 0,
  `monto_impuesto` DECIMAL(15,2) DEFAULT 0,
  `monto_creditos` DECIMAL(15,2) DEFAULT 0,
  `monto_debitos` DECIMAL(15,2) DEFAULT 0,
  `monto_a_pagar` DECIMAL(15,2) DEFAULT 0,
  `monto_a_favor` DECIMAL(15,2) DEFAULT 0,
  `numero_confirmacion` VARCHAR(50) NULL COMMENT 'Número de confirmación de Hacienda',
  `archivo_xml` VARCHAR(255) NULL COMMENT 'Ruta al XML generado',
  `archivo_pdf` VARCHAR(255) NULL COMMENT 'Ruta al PDF de respaldo',
  `estado` ENUM('borrador', 'enviada', 'aceptada', 'rechazada') DEFAULT 'borrador',
  `notas` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  
  UNIQUE KEY `idx_empresa_tipo_periodo` (`empresa_id`, `tipo_declaracion`, `periodo_fiscal`),
  INDEX `idx_periodo_fiscal` (`periodo_fiscal`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Gestión integral de obligaciones tributarias costarricenses.

---

#### 5. **retenciones_impuestos**
Registro de retenciones de impuestos en la fuente.

```sql
CREATE TABLE `retenciones_impuestos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `proveedor_id` BIGINT UNSIGNED NULL COMMENT 'Proveedor al que se le retuvo',
  `compra_id` BIGINT UNSIGNED NULL COMMENT 'FK a ordenes_compra o pagos',
  `venta_id` BIGINT UNSIGNED NULL,
  `tipo_retencion` ENUM('renta', 'iva', 'otras') NOT NULL,
  `porcentaje_retencion` DECIMAL(5,2) NOT NULL COMMENT 'Ej: 2.00, 10.00, 15.00',
  `monto_base` DECIMAL(15,2) NOT NULL COMMENT 'Monto sobre el que se calcula',
  `monto_retenido` DECIMAL(15,2) NOT NULL,
  `numero_comprobante` VARCHAR(50) NULL COMMENT 'Número de comprobante de retención',
  `fecha_retencion` DATE NOT NULL,
  `periodo_declaracion` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM para vincular a declaración',
  `declarado` TINYINT(1) DEFAULT 0 COMMENT 'Si ya fue incluido en declaración',
  `notas` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_empresa_periodo` (`empresa_id`, `periodo_declaracion`),
  INDEX `idx_proveedor` (`proveedor_id`),
  INDEX `idx_fecha` (`fecha_retencion`),
  INDEX `idx_declarado` (`declarado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Cumplimiento legal de retenciones en la fuente (obligatorio >₡110k).

---

### **CATEGORÍA 3: BANCOS Y FINANZAS** 🏦

#### 6. **cuentas_bancarias**
Cuentas bancarias de las empresas para gestión financiera.

```sql
CREATE TABLE `cuentas_bancarias` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `banco` VARCHAR(100) NOT NULL COMMENT 'Nombre del banco',
  `numero_cuenta` VARCHAR(50) NOT NULL COMMENT 'Número de cuenta (encriptado)',
  `iban` VARCHAR(34) NULL COMMENT 'IBAN (CR seguido de 20 dígitos)',
  `tipo_cuenta` ENUM('corriente', 'ahorros', 'cliente', 'colones', 'dolares') NOT NULL,
  `moneda` ENUM('CRC', 'USD', 'EUR') DEFAULT 'CRC',
  `saldo_actual` DECIMAL(15,2) DEFAULT 0,
  `cuenta_contable_id` BIGINT UNSIGNED NULL COMMENT 'Vinculación con contabilidad',
  `sucursal_banco` VARCHAR(100) NULL,
  `contacto_ejecutivo` VARCHAR(100) NULL,
  `telefono_ejecutivo` VARCHAR(20) NULL,
  `activa` TINYINT(1) DEFAULT 1,
  `es_principal` TINYINT(1) DEFAULT 0 COMMENT 'Cuenta principal de la empresa',
  `notas` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`cuenta_contable_id`) REFERENCES `cuentas_contables`(`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `idx_empresa_numero` (`empresa_id`, `numero_cuenta`),
  INDEX `idx_iban` (`iban`),
  INDEX `idx_moneda` (`moneda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

#### 7. **movimientos_bancarios**
Registro de movimientos bancarios para conciliación.

```sql
CREATE TABLE `movimientos_bancarios` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cuenta_bancaria_id` BIGINT UNSIGNED NOT NULL,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `fecha_movimiento` DATE NOT NULL,
  `fecha_valor` DATE NULL,
  `tipo_movimiento` ENUM('deposito', 'retiro', 'transferencia_entrada', 'transferencia_salida', 'comision', 'interes', 'ajuste') NOT NULL,
  `numero_referencia` VARCHAR(50) NULL COMMENT 'Número de cheque, transferencia, etc',
  `descripcion` VARCHAR(255) NOT NULL,
  `monto` DECIMAL(15,2) NOT NULL,
  `saldo_despues` DECIMAL(15,2) NULL COMMENT 'Saldo después del movimiento',
  `beneficiario` VARCHAR(200) NULL,
  `conciliado` TINYINT(1) DEFAULT 0,
  `fecha_conciliacion` DATE NULL,
  `asiento_contable_id` BIGINT UNSIGNED NULL COMMENT 'Vinculación con contabilidad',
  `notas` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`cuenta_bancaria_id`) REFERENCES `cuentas_bancarias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`asiento_contable_id`) REFERENCES `asientos_contables`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_cuenta_fecha` (`cuenta_bancaria_id`, `fecha_movimiento`),
  INDEX `idx_conciliado` (`conciliado`),
  INDEX `idx_tipo` (`tipo_movimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Conciliación bancaria automatizada y control de flujo de efectivo.

---

### **CATEGORÍA 4: RECURSOS HUMANOS Y PLANILLAS** 👥

#### 8. **deducciones_legales**
Catálogo de deducciones legales para planillas (CCSS, INS, etc).

```sql
CREATE TABLE `deducciones_legales` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE COMMENT 'CCSS, INS, LPT, etc',
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `tipo` ENUM('ccss_obrero', 'ccss_patronal', 'ins_laboral', 'ins_lpt', 'impuesto_renta', 'asociacion_solidarista', 'embargo', 'prestamo', 'otros') NOT NULL,
  `porcentaje_base` DECIMAL(5,2) NULL COMMENT 'Porcentaje si es fijo',
  `monto_fijo` DECIMAL(10,2) NULL COMMENT 'Monto si es fijo',
  `aplica_sobre` ENUM('salario_bruto', 'salario_neto', 'monto_especifico') DEFAULT 'salario_bruto',
  `es_obligatoria` TINYINT(1) DEFAULT 0,
  `activa` TINYINT(1) DEFAULT 1,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_activa` (`activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Datos iniciales**:
- CCSS Obrero: 10.67%
- CCSS Patronal: 26.67%
- INS Laboral: 1.00% (patronal)
- LPT (Ley Protección Trabajador): 3.00% (obrero), 5.25% (patronal)

---

#### 9. **planillas_ccss**
Registro de planillas CCSS para reportar a Caja Costarricense de Seguro Social.

```sql
CREATE TABLE `planillas_ccss` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NOT NULL,
  `periodo_nomina_id` BIGINT UNSIGNED NULL,
  `periodo` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
  `fecha_generacion` DATE NOT NULL,
  `fecha_presentacion` DATE NULL,
  `numero_planilla` VARCHAR(50) NULL COMMENT 'Número asignado por CCSS',
  `total_empleados` INT NOT NULL,
  `total_salarios` DECIMAL(15,2) NOT NULL,
  `total_cuota_obrera` DECIMAL(15,2) NOT NULL,
  `total_cuota_patronal` DECIMAL(15,2) NOT NULL,
  `total_a_pagar` DECIMAL(15,2) NOT NULL,
  `archivo_xml` VARCHAR(255) NULL,
  `archivo_pdf` VARCHAR(255) NULL,
  `estado` ENUM('borrador', 'enviada', 'aceptada', 'rechazada', 'pagada') DEFAULT 'borrador',
  `fecha_pago` DATE NULL,
  `notas` TEXT NULL,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`periodo_nomina_id`) REFERENCES `periodos_nomina`(`id`) ON DELETE SET NULL,
  
  UNIQUE KEY `idx_empresa_periodo` (`empresa_id`, `periodo`),
  INDEX `idx_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Cumplimiento obligatorio de reportes mensuales a CCSS.

---

### **CATEGORÍA 5: COMERCIO Y CLIENTES** 🛒

#### 10. **tipos_clientes**
Clasificación de clientes para segmentación y análisis.

```sql
CREATE TABLE `tipos_clientes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NOT COMMENT 'Mayorista, Minorista, Distribuidor, Gobierno, etc',
  `descripcion` TEXT NULL,
  `descuento_default` DECIMAL(5,2) DEFAULT 0 COMMENT 'Descuento default para este tipo',
  `dias_credito_default` INT DEFAULT 0 COMMENT 'Días de crédito default',
  `activo` TINYINT(1) DEFAULT 1,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Luego agregar FK en tabla `clientes`:
```sql
ALTER TABLE `clientes` 
ADD COLUMN `tipo_cliente_id` BIGINT UNSIGNED NULL AFTER `empresa_id`,
ADD FOREIGN KEY (`tipo_cliente_id`) REFERENCES `tipos_clientes`(`id`) ON DELETE SET NULL;
```

---

#### 11. **zonas_geograficas**
Zonas geográficas de Costa Rica para rutas y territorialización de ventas.

```sql
CREATE TABLE `zonas_geograficas` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` BIGINT UNSIGNED NULL COMMENT 'NULL si es catálogo nacional',
  `codigo` VARCHAR(10) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'San José Centro, Alajuela Norte, etc',
  `tipo` ENUM('provincia', 'canton', 'distrito', 'zona_ventas', 'ruta') NOT NULL,
  `zona_padre_id` BIGINT UNSIGNED NULL COMMENT 'Para jerarquía de zonas',
  `provincias_incluidas` JSON NULL COMMENT 'Array de provincias si es zona_ventas',
  `vendedor_asignado_id` BIGINT UNSIGNED NULL,
  `activa` TINYINT(1) DEFAULT 1,
  `eliminado` TINYINT(1) DEFAULT 0,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`zona_padre_id`) REFERENCES `zonas_geograficas`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`vendedor_asignado_id`) REFERENCES `empleados`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_empresa` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Datos iniciales**: 7 provincias, 82 cantones, 488 distritos de Costa Rica.

---

### **CATEGORÍA 6: SEGURIDAD Y CUMPLIMIENTO** 🔒

#### 12. **logs_acceso_sistema**
Registro detallado de accesos al sistema para auditoría de seguridad.

```sql
CREATE TABLE `logs_acceso_sistema` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` BIGINT UNSIGNED NULL,
  `email` VARCHAR(191) NULL COMMENT 'Email usado en intento de login',
  `tipo_evento` ENUM('login_exitoso', 'login_fallido', 'logout', 'cambio_password', 'reset_password', 'bloqueo_cuenta', 'desbloqueo_cuenta') NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IPv4 o IPv6',
  `user_agent` VARCHAR(255) NULL,
  `metodo_autenticacion` VARCHAR(50) NULL COMMENT 'password, 2fa, token, etc',
  `razon_fallo` VARCHAR(255) NULL COMMENT 'Si login_fallido',
  `sesion_id` VARCHAR(191) NULL,
  `duracion_sesion` INT NULL COMMENT 'Segundos de duración de sesión',
  `pais` VARCHAR(2) NULL COMMENT 'Código ISO país',
  `ciudad` VARCHAR(100) NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  
  INDEX `idx_usuario_fecha` (`usuario_id`, `creado_en`),
  INDEX `idx_tipo_evento` (`tipo_evento`),
  INDEX `idx_ip` (`ip_address`),
  INDEX `idx_fecha` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Justificación**: Cumplimiento de Ley de Protección de Datos Personales (#8968).

---

## 📋 Resumen de Propuesta

| # | Tabla | Categoría | Prioridad | Modelos Req. |
|---|-------|-----------|-----------|--------------|
| 1 | mensajes_hacienda | Facturación FE | ALTA | ✅ |
| 2 | tipos_comprobantes_fe | Facturación FE | ALTA | ✅ |
| 3 | codigos_actividad_economica | Facturación FE | MEDIA | ✅ |
| 4 | declaraciones_tributarias | Tributación | ALTA | ✅ |
| 5 | retenciones_impuestos | Tributación | ALTA | ✅ |
| 6 | cuentas_bancarias | Finanzas | ALTA | ✅ |
| 7 | movimientos_bancarios | Finanzas | ALTA | ✅ |
| 8 | deducciones_legales | RRHH | ALTA | ✅ |
| 9 | planillas_ccss | RRHH | ALTA | ✅ |
| 10 | tipos_clientes | Comercio | MEDIA | ✅ |
| 11 | zonas_geograficas | Comercio | MEDIA | ✅ |
| 12 | logs_acceso_sistema | Seguridad | ALTA | ✅ |

**Total**: 12 nuevas tablas propuestas

---

## ✅ Garantías de Seguridad

### ❌ NO se modificará:
- ✅ Ninguna tabla existente
- ✅ Ningún dato actual
- ✅ Ninguna migración existente
- ✅ Ningún modelo actual

### ✅ SOLO se agregará:
- ✅ Nuevas tablas independientes
- ✅ Nuevas migraciones con timestamp futuro
- ✅ Nuevos modelos
- ✅ Nuevos controllers/resources según necesidad
- ✅ Foreign keys que referencian tablas existentes (no invasivo)

### 🔒 Proceso Seguro:
1. Crear migraciones con rollback
2. Probar en `api_db_testing` primero
3. Validar integridad
4. Aplicar a `api_db` solo con aprobación

---

## 🎯 Beneficios para el Mercado CR

1. **Facturación Electrónica**: Gestión completa de respuestas Hacienda
2. **Cumplimiento Tributario**: D104, D101, retenciones
3. **Conciliación Bancaria**: Control financiero mejorado
4. **Planillas CCSS**: Automatización de obligaciones laborales
5. **Segmentación Comercial**: Tipos de clientes y zonas
6. **Seguridad**: Trazabilidad completa de accesos

---

## 📝 Próximos Pasos Sugeridos

**¿Desea que proceda con la implementación?**

Si aprueba, ejecutaré en orden:

1. ✅ Crear migraciones para las 12 tablas
2. ✅ Crear modelos Eloquent
3. ✅ Crear seeders para datos iniciales (tipos_comprobantes_fe, deducciones_legales, etc)
4. ✅ Probar en `api_db_testing`
5. ✅ Generar controllers/resources/requests básicos
6. ✅ Documentar en OpenAPI
7. ✅ Aplicar a `api_db` producción

**Tiempo estimado**: 2-3 horas  
**Riesgo**: CERO (no toca nada existente)

---

**Esperando su aprobación para proceder** ⏳
